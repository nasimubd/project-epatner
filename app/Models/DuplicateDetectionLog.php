<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuplicateDetectionLog extends Model
{
    use HasFactory;

    protected $connection = 'mysql_common';
    protected $table = 'tbl_duplicate_detection_log';

    protected $fillable = [
        'customer_id',
        'detection_type',
        'confidence_score',
        'potential_duplicates',
        'detection_criteria',
        'detection_status',
        'resolution_action',
        'resolved_by',
        'resolved_at',
        'resolution_notes'
    ];

    protected $casts = [
        'potential_duplicates' => 'array',
        'detection_criteria' => 'array',
        'confidence_score' => 'decimal:2',
        'resolved_at' => 'datetime'
    ];

    // RELATIONSHIPS

    /**
     * Get the customer this detection log belongs to
     */
    public function customer()
    {
        return $this->belongsTo(CustomerLedger::class, 'customer_id', 'ledger_id');
    }

    /**
     * Get the user who resolved this detection
     */
    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // SCOPES

    /**
     * Scope for pending detections
     */
    public function scopePending($query)
    {
        return $query->where('detection_status', 'pending');
    }

    /**
     * Scope for resolved detections
     */
    public function scopeResolved($query)
    {
        return $query->whereIn('detection_status', ['reviewed', 'merged', 'dismissed']);
    }

    /**
     * Scope for high confidence detections
     */
    public function scopeHighConfidence($query, $threshold = 80)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    /**
     * Scope by detection type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('detection_type', $type);
    }

    // METHODS

    /**
     * Mark detection as reviewed
     */
    public function markAsReviewed($userId, $notes = null)
    {
        $this->update([
            'detection_status' => 'reviewed',
            'resolution_action' => 'reviewed',
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution_notes' => $notes
        ]);
    }

    /**
     * Mark detection as merged
     */
    public function markAsMerged($userId, $notes = null)
    {
        $this->update([
            'detection_status' => 'merged',
            'resolution_action' => 'merged',
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution_notes' => $notes
        ]);
    }

    /**
     * Mark detection as dismissed (not a duplicate)
     */
    public function markAsDismissed($userId, $notes = null)
    {
        $this->update([
            'detection_status' => 'dismissed',
            'resolution_action' => 'marked_not_duplicate',
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution_notes' => $notes
        ]);
    }

    /**
     * Get potential duplicate customers
     */
    public function getPotentialDuplicateCustomers()
    {
        $duplicateIds = collect($this->potential_duplicates)->pluck('customer_id')->toArray();

        return CustomerLedger::on('mysql_common')
            ->whereIn('ledger_id', $duplicateIds)
            ->get()
            ->map(function ($customer) {
                $duplicateInfo = collect($this->potential_duplicates)
                    ->firstWhere('customer_id', $customer->ledger_id);

                $customer->duplicate_confidence = $duplicateInfo['confidence'] ?? 0;
                $customer->duplicate_reason = $duplicateInfo['reason'] ?? '';

                return $customer;
            });
    }

    /**
     * Get detection statistics
     */
    public static function getDetectionStatistics($dateFrom = null, $dateTo = null)
    {
        $query = static::query();

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        return [
            'total_detections' => $query->count(),
            'pending' => $query->clone()->where('detection_status', 'pending')->count(),
            'resolved' => $query->clone()->whereIn('detection_status', ['reviewed', 'merged', 'dismissed'])->count(),
            'merged' => $query->clone()->where('detection_status', 'merged')->count(),
            'dismissed' => $query->clone()->where('detection_status', 'dismissed')->count(),
            'avg_confidence' => round($query->avg('confidence_score'), 2),
            'by_type' => $query->clone()->groupBy('detection_type')->selectRaw('detection_type, COUNT(*) as count')->pluck('count', 'detection_type'),
            'high_confidence' => $query->clone()->where('confidence_score', '>=', 80)->count()
        ];
    }
}
