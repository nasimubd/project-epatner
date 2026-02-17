<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CustomerImportHistory extends Model
{
    use HasFactory;

    protected $table = 'customer_import_history';

    protected $fillable = [
        'business_id',
        'import_batch_id',
        'district',
        'sub_district',
        'village',
        'import_filters',
        'total_available',
        'total_selected',
        'total_imported',
        'total_skipped',
        'total_failed',
        'imported_customer_ids',
        'skipped_customers',
        'failed_customers',
        'conflict_resolutions',
        'import_status',
        'import_notes',
        'started_at',
        'completed_at',
        'processing_time_seconds',
        'imported_by',
        'import_method'
    ];

    protected $casts = [
        'import_filters' => 'array',
        'imported_customer_ids' => 'array',
        'skipped_customers' => 'array',
        'failed_customers' => 'array',
        'conflict_resolutions' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function conflicts()
    {
        return $this->hasMany(CustomerImportConflict::class, 'import_batch_id', 'import_batch_id');
    }

    public function getSuccessRateAttribute()
    {
        if ($this->total_selected == 0) {
            return 0;
        }

        return round(($this->total_imported / $this->total_selected) * 100, 2);
    }

    public function getDurationAttribute()
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffForHumans($this->completed_at, true);
    }

    public function getLocationLabelAttribute()
    {
        $parts = array_filter([$this->village, $this->sub_district, $this->district]);
        return implode(', ', $parts) ?: 'All Locations';
    }

    public static function generateBatchId($businessId)
    {
        return 'IMP_' . $businessId . '_' . now()->format('YmdHis') . '_' . substr(uniqid(), -4);
    }
}
