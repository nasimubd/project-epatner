<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuplicateDetectionPerformance extends Model
{
    use HasFactory;

    protected $connection = 'mysql_common';
    protected $table = 'tbl_duplicate_detection_performance';

    protected $fillable = [
        'detection_date',
        'total_customers_scanned',
        'duplicates_detected',
        'auto_merged',
        'manual_reviews_required',
        'average_detection_time',
        'average_confidence_score',
        'detection_type_breakdown',
        'performance_metrics'
    ];

    protected $casts = [
        'detection_date' => 'date',
        'average_detection_time' => 'decimal:3',
        'average_confidence_score' => 'decimal:2',
        'detection_type_breakdown' => 'array',
        'performance_metrics' => 'array'
    ];

    // SCOPES

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('detection_date', [$from, $to]);
    }

    /**
     * Scope for recent performance
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('detection_date', '>=', now()->subDays($days));
    }

    // METHODS

    /**
     * Record daily performance metrics
     */
    public static function recordDailyPerformance($date, $metrics)
    {
        return static::updateOrCreate(
            ['detection_date' => $date],
            $metrics
        );
    }

    /**
     * Get performance trends
     */
    public static function getPerformanceTrends($days = 30)
    {
        $records = static::where('detection_date', '>=', now()->subDays($days))
            ->orderBy('detection_date')
            ->get();

        return [
            'daily_data' => $records,
            'trends' => [
                'total_scanned' => $records->sum('total_customers_scanned'),
                'total_duplicates' => $records->sum('duplicates_detected'),
                'total_auto_merged' => $records->sum('auto_merged'),
                'avg_detection_time' => round($records->avg('average_detection_time'), 3),
                'avg_confidence_score' => round($records->avg('average_confidence_score'), 2),
                'duplicate_rate' => $records->sum('total_customers_scanned') > 0
                    ? round(($records->sum('duplicates_detected') / $records->sum('total_customers_scanned')) * 100, 2)
                    : 0,
                'auto_merge_rate' => $records->sum('duplicates_detected') > 0
                    ? round(($records->sum('auto_merged') / $records->sum('duplicates_detected')) * 100, 2)
                    : 0,
                'manual_review_rate' => $records->sum('duplicates_detected') > 0
                    ? round(($records->sum('manual_reviews_required') / $records->sum('duplicates_detected')) * 100, 2)
                    : 0
            ],
            'performance_indicators' => [
                'detection_efficiency' => $records->avg('average_detection_time') < 1.0 ? 'excellent' : ($records->avg('average_detection_time') < 2.0 ? 'good' : 'needs_improvement'),
                'accuracy_trend' => static::calculateAccuracyTrend($records),
                'volume_trend' => static::calculateVolumeTrend($records)
            ]
        ];
    }

    /**
     * Calculate accuracy trend
     */
    private static function calculateAccuracyTrend($records)
    {
        if ($records->count() < 2) return 'insufficient_data';

        $recent = $records->take(-7)->avg('average_confidence_score');
        $previous = $records->take(-14)->skip(-7)->avg('average_confidence_score');

        if ($recent > $previous + 2) return 'improving';
        if ($recent < $previous - 2) return 'declining';
        return 'stable';
    }

    /**
     * Calculate volume trend
     */
    private static function calculateVolumeTrend($records)
    {
        if ($records->count() < 2) return 'insufficient_data';

        $recent = $records->take(-7)->avg('duplicates_detected');
        $previous = $records->take(-14)->skip(-7)->avg('duplicates_detected');

        if ($recent > $previous * 1.2) return 'increasing';
        if ($recent < $previous * 0.8) return 'decreasing';
        return 'stable';
    }

    /**
     * Get system health metrics
     */
    public static function getSystemHealthMetrics()
    {
        $latest = static::latest('detection_date')->first();
        $weekAvg = static::recent(7)->get();

        if (!$latest) {
            return [
                'status' => 'no_data',
                'message' => 'No performance data available'
            ];
        }

        $health = [
            'overall_status' => 'healthy',
            'last_scan_date' => $latest->detection_date,
            'metrics' => [
                'detection_speed' => [
                    'current' => $latest->average_detection_time,
                    'status' => $latest->average_detection_time < 1.0 ? 'excellent' : ($latest->average_detection_time < 2.0 ? 'good' : 'slow'),
                    'weekly_avg' => round($weekAvg->avg('average_detection_time'), 3)
                ],
                'accuracy' => [
                    'current' => $latest->average_confidence_score,
                    'status' => $latest->average_confidence_score >= 85 ? 'excellent' : ($latest->average_confidence_score >= 75 ? 'good' : 'needs_improvement'),
                    'weekly_avg' => round($weekAvg->avg('average_confidence_score'), 2)
                ],
                'efficiency' => [
                    'auto_merge_rate' => $latest->duplicates_detected > 0
                        ? round(($latest->auto_merged / $latest->duplicates_detected) * 100, 2)
                        : 0,
                    'manual_review_rate' => $latest->duplicates_detected > 0
                        ? round(($latest->manual_reviews_required / $latest->duplicates_detected) * 100, 2)
                        : 0
                ]
            ],
            'alerts' => []
        ];

        // Check for performance issues
        if ($latest->average_detection_time > 3.0) {
            $health['alerts'][] = [
                'type' => 'performance',
                'severity' => 'warning',
                'message' => 'Detection time is slower than optimal'
            ];
            $health['overall_status'] = 'warning';
        }

        if ($latest->average_confidence_score < 70) {
            $health['alerts'][] = [
                'type' => 'accuracy',
                'severity' => 'error',
                'message' => 'Detection accuracy is below acceptable threshold'
            ];
            $health['overall_status'] = 'error';
        }

        if ($latest->manual_reviews_required > ($latest->duplicates_detected * 0.8)) {
            $health['alerts'][] = [
                'type' => 'efficiency',
                'severity' => 'warning',
                'message' => 'High manual review rate indicates need for algorithm tuning'
            ];
            if ($health['overall_status'] === 'healthy') {
                $health['overall_status'] = 'warning';
            }
        }

        return $health;
    }
}
