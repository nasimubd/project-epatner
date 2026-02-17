<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CustomerMergeHistory extends Model
{
    use HasFactory;

    protected $connection = 'mysql_common';
    protected $table = 'tbl_customer_merge_history';

    protected $fillable = [
        'primary_customer_id',
        'merged_customer_id',
        'merge_strategy',
        'merge_confidence',
        'pre_merge_primary_data',
        'pre_merge_merged_data',
        'post_merge_data',
        'merge_criteria',
        'merge_source',
        'merged_by',
        'merge_notes',
        'is_reversible',
        'merged_at'
    ];

    protected $casts = [
        'merge_confidence' => 'decimal:2',
        'pre_merge_primary_data' => 'array',
        'pre_merge_merged_data' => 'array',
        'post_merge_data' => 'array',
        'merge_criteria' => 'array',
        'is_reversible' => 'boolean',
        'merged_at' => 'datetime'
    ];

    // RELATIONSHIPS

    /**
     * Get the primary customer (the one that remains)
     */
    public function primaryCustomer()
    {
        return $this->belongsTo(CustomerLedger::class, 'primary_customer_id', 'ledger_id');
    }

    /**
     * Get the merged customer (the one that was merged)
     */
    public function mergedCustomer()
    {
        return $this->belongsTo(CustomerLedger::class, 'merged_customer_id', 'ledger_id');
    }

    /**
     * Get the user who performed the merge
     */
    public function mergedByUser()
    {
        return $this->belongsTo(User::class, 'merged_by');
    }

    // SCOPES

    /**
     * Scope for reversible merges
     */
    public function scopeReversible($query)
    {
        return $query->where('is_reversible', true);
    }

    /**
     * Scope by merge source
     */
    public function scopeBySource($query, $source)
    {
        return $query->where('merge_source', $source);
    }

    /**
     * Scope for high confidence merges
     */
    public function scopeHighConfidence($query, $threshold = 80)
    {
        return $query->where('merge_confidence', '>=', $threshold);
    }

    /**
     * Scope for recent merges
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('merged_at', '>=', now()->subDays($days));
    }

    // METHODS

    /**
     * Reverse the merge if possible
     */
    public function reverseMerge($userId, $reason = null)
    {
        if (!$this->is_reversible) {
            throw new \Exception('This merge is not reversible');
        }

        DB::beginTransaction();

        try {
            // Restore the merged customer
            $mergedCustomer = CustomerLedger::on('mysql_common')
                ->withTrashed()
                ->find($this->merged_customer_id);

            if (!$mergedCustomer) {
                throw new \Exception('Merged customer record not found');
            }

            // Restore original data
            $mergedCustomer->update(array_merge(
                $this->pre_merge_merged_data,
                [
                    'is_merged' => false,
                    'merged_into' => null
                ]
            ));

            // Restore primary customer to original state
            $primaryCustomer = $this->primaryCustomer;
            $primaryCustomer->update($this->pre_merge_primary_data);

            // Mark this merge as reversed
            $this->update([
                'is_reversible' => false,
                'merge_notes' => ($this->merge_notes ?? '') . "\n\nReversed on " . now() . " by user {$userId}. Reason: " . ($reason ?? 'No reason provided')
            ]);

            // Create audit log
            CustomerLedgerAudit::create([
                'ledger_id' => $this->primary_customer_id,
                'action' => 'merge_reversed',
                'action_category' => 'merge',
                'action_source' => 'manual',
                'old_data' => $this->post_merge_data,
                'new_data' => $this->pre_merge_primary_data,
                'changed_by' => $userId,
                'reason' => $reason,
                'related_customer_id' => $this->merged_customer_id
            ]);

            CustomerLedgerAudit::create([
                'ledger_id' => $this->merged_customer_id,
                'action' => 'restored',
                'action_category' => 'merge',
                'action_source' => 'manual',
                'old_data' => ['is_merged' => true],
                'new_data' => $this->pre_merge_merged_data,
                'changed_by' => $userId,
                'reason' => $reason,
                'related_customer_id' => $this->primary_customer_id
            ]);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get merge summary
     */
    public function getMergeSummary()
    {
        return [
            'merge_id' => $this->id,
            'primary_customer' => [
                'id' => $this->primary_customer_id,
                'name' => $this->primaryCustomer->ledger_name ?? 'Unknown',
                'pre_merge_data' => $this->pre_merge_primary_data
            ],
            'merged_customer' => [
                'id' => $this->merged_customer_id,
                'name' => $this->pre_merge_merged_data['ledger_name'] ?? 'Unknown',
                'pre_merge_data' => $this->pre_merge_merged_data
            ],
            'merge_details' => [
                'strategy' => $this->merge_strategy,
                'confidence' => $this->merge_confidence,
                'criteria' => $this->merge_criteria,
                'source' => $this->merge_source,
                'merged_by' => $this->merged_by,
                'merged_at' => $this->merged_at,
                'is_reversible' => $this->is_reversible
            ],
            'result_data' => $this->post_merge_data
        ];
    }

    /**
     * Get merge statistics
     */
    public static function getMergeStatistics($dateFrom = null, $dateTo = null, $shopId = null)
    {
        $query = static::query();

        if ($dateFrom) {
            $query->where('merged_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('merged_at', '<=', $dateTo);
        }

        if ($shopId) {
            $query->whereHas('primaryCustomer', function ($q) use ($shopId) {
                $q->where('shop_id', $shopId);
            });
        }

        return [
            'total_merges' => $query->count(),
            'by_source' => $query->clone()->groupBy('merge_source')->selectRaw('merge_source, COUNT(*) as count')->pluck('count', 'merge_source'),
            'by_strategy' => $query->clone()->groupBy('merge_strategy')->selectRaw('merge_strategy, COUNT(*) as count')->pluck('count', 'merge_strategy'),
            'average_confidence' => round($query->avg('merge_confidence'), 2),
            'reversible_merges' => $query->clone()->where('is_reversible', true)->count(),
            'high_confidence_merges' => $query->clone()->where('merge_confidence', '>=', 80)->count(),
            'recent_merges' => $query->clone()->where('merged_at', '>=', now()->subDays(7))->count()
        ];
    }
}
