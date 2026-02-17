<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerLedgerAudit extends Model
{
    use HasFactory;

    protected $connection = 'mysql_common';
    protected $table = 'tbl_customer_ledger_audit';

    protected $fillable = [
        'ledger_id',
        'action',
        'action_category',
        'action_source',
        'old_data',
        'new_data',
        'changed_by',
        'reason',
        'confidence_score',
        'detection_metadata',
        'related_customer_id',
        'ip_address',
        'user_agent',
        'session_id'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'detection_metadata' => 'array',
        'confidence_score' => 'decimal:2'
    ];

    // RELATIONSHIPS

    /**
     * Get the customer this audit log belongs to
     */
    public function customer()
    {
        return $this->belongsTo(CustomerLedger::class, 'ledger_id', 'ledger_id');
    }

    /**
     * Get the related customer (for merge operations)
     */
    public function relatedCustomer()
    {
        return $this->belongsTo(CustomerLedger::class, 'related_customer_id', 'ledger_id');
    }

    /**
     * Get the user who made the change
     */
    public function changedByUser()
    {
        return $this->belongsTo(User::class, 'changed_by', 'name');
    }

    // SCOPES

    /**
     * Scope by action
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope by action category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('action_category', $category);
    }

    /**
     * Scope by action source
     */
    public function scopeBySource($query, $source)
    {
        return $query->where('action_source', $source);
    }

    /**
     * Scope for duplicate-related actions
     */
    public function scopeDuplicateActions($query)
    {
        return $query->where('action_category', 'duplicate')
            ->orWhere('action_category', 'merge');
    }

    /**
     * Scope for recent activities
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for high confidence actions
     */
    public function scopeHighConfidence($query, $threshold = 80)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // METHODS

    /**
     * Get formatted action description
     */
    public function getActionDescription()
    {
        $descriptions = [
            'created' => 'Customer record created',
            'updated' => 'Customer information updated',
            'merged_primary' => 'Customer merged (kept as primary)',
            'merged_secondary' => 'Customer merged (merged into another)',
            'merge_reversed' => 'Customer merge reversed',
            'restored' => 'Customer record restored',
            'deleted' => 'Customer record deleted',
            'duplicate_detected' => 'Potential duplicate detected',
            'duplicate_resolved' => 'Duplicate issue resolved',
            'quality_updated' => 'Data quality score updated',
            'bulk_imported' => 'Customer imported via bulk upload',
            'api_created' => 'Customer created via API',
            'api_updated' => 'Customer updated via API'
        ];

        return $descriptions[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }

    /**
     * Get changes summary
     */
    public function getChangesSummary()
    {
        if (empty($this->old_data) || empty($this->new_data)) {
            return [];
        }

        $changes = [];
        $oldData = $this->old_data;
        $newData = $this->new_data;

        foreach ($newData as $field => $newValue) {
            $oldValue = $oldData[$field] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                    'field_label' => $this->getFieldLabel($field)
                ];
            }
        }

        return $changes;
    }

    /**
     * Get human-readable field labels
     */
    private function getFieldLabel($field)
    {
        $labels = [
            'ledger_name' => 'Customer Name',
            'contact_number' => 'Phone Number',
            'district' => 'District',
            'sub_district' => 'Sub District',
            'village' => 'Village',
            'landmark' => 'Landmark',
            'type' => 'Customer Type',
            'location' => 'Location',
            'is_merged' => 'Merge Status',
            'merged_into' => 'Merged Into',
            'data_quality_score' => 'Quality Score'
        ];

        return $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * Get risk level based on action and confidence
     */
    public function getRiskLevel()
    {
        // High risk actions
        $highRiskActions = ['merged_primary', 'merged_secondary', 'deleted', 'merge_reversed'];

        if (in_array($this->action, $highRiskActions)) {
            return 'high';
        }

        // Medium risk for low confidence duplicate detections
        if ($this->action === 'duplicate_detected' && $this->confidence_score < 70) {
            return 'medium';
        }

        // Low risk for routine updates
        return 'low';
    }

    /**
     * Check if this audit log represents a critical action
     */
    public function isCriticalAction()
    {
        $criticalActions = [
            'merged_primary',
            'merged_secondary',
            'deleted',
            'merge_reversed',
            'restored'
        ];

        return in_array($this->action, $criticalActions);
    }

    /**
     * Get audit statistics
     */
    public static function getAuditStatistics($dateFrom = null, $dateTo = null, $customerId = null)
    {
        $query = static::query();

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        if ($customerId) {
            $query->where('ledger_id', $customerId);
        }

        return [
            'total_actions' => $query->count(),
            'by_action' => $query->clone()->groupBy('action')->selectRaw('action, COUNT(*) as count')->pluck('count', 'action'),
            'by_category' => $query->clone()->groupBy('action_category')->selectRaw('action_category, COUNT(*) as count')->pluck('count', 'action_category'),
            'by_source' => $query->clone()->groupBy('action_source')->selectRaw('action_source, COUNT(*) as count')->pluck('count', 'action_source'),
            'critical_actions' => $query->clone()->whereIn('action', [
                'merged_primary',
                'merged_secondary',
                'deleted',
                'merge_reversed',
                'restored'
            ])->count(),
            'duplicate_related' => $query->clone()->whereIn('action_category', ['duplicate', 'merge'])->count(),
            'recent_activity' => $query->clone()->where('created_at', '>=', now()->subDays(7))->count(),
            'unique_customers_affected' => $query->clone()->distinct('ledger_id')->count('ledger_id'),
            'average_confidence' => round($query->whereNotNull('confidence_score')->avg('confidence_score'), 2)
        ];
    }

    /**
     * Get activity timeline for a customer
     */
    public static function getCustomerTimeline($customerId, $limit = 50)
    {
        return static::where('ledger_id', $customerId)
            ->orWhere('related_customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($audit) {
                return [
                    'id' => $audit->id,
                    'action' => $audit->action,
                    'description' => $audit->getActionDescription(),
                    'category' => $audit->action_category,
                    'source' => $audit->action_source,
                    'changed_by' => $audit->changed_by,
                    'changes' => $audit->getChangesSummary(),
                    'risk_level' => $audit->getRiskLevel(),
                    'confidence_score' => $audit->confidence_score,
                    'created_at' => $audit->created_at,
                    'reason' => $audit->reason
                ];
            });
    }

    /**
     * Get system activity summary
     */
    public static function getSystemActivitySummary($days = 7)
    {
        $query = static::where('created_at', '>=', now()->subDays($days));

        $dailyActivity = $query->clone()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topActions = $query->clone()
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        $criticalActions = $query->clone()
            ->whereIn('action', ['merged_primary', 'merged_secondary', 'deleted', 'merge_reversed'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return [
            'daily_activity' => $dailyActivity,
            'top_actions' => $topActions,
            'critical_actions' => $criticalActions,
            'total_activity' => $query->count(),
            'unique_customers' => $query->distinct('ledger_id')->count('ledger_id'),
            'duplicate_detections' => $query->clone()->where('action', 'duplicate_detected')->count(),
            'merges_performed' => $query->clone()->whereIn('action', ['merged_primary', 'merged_secondary'])->count() / 2
        ];
    }

    /**
     * Create audit log for customer action
     */
    public static function logCustomerAction($customerId, $action, $options = [])
    {
        return static::create(array_merge([
            'ledger_id' => $customerId,
            'action' => $action,
            'action_category' => $options['category'] ?? 'system',
            'action_source' => $options['source'] ?? 'manual',
            'changed_by' => $options['user'] ?? (\Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user()->name : 'system'),
            'ip_address' => request()->ip(),
            'changed_by' => $options['user'] ?? (\Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user()->name : 'system'),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId()
        ], $options));
    }
}
