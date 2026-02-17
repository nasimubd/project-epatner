<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerDataQuality extends Model
{
    use HasFactory;

    protected $connection = 'mysql_common';
    protected $table = 'tbl_customer_data_quality';

    protected $fillable = [
        'customer_id',
        'quality_score',
        'quality_grade',
        'quality_factors',
        'missing_fields',
        'validation_errors',
        'improvement_suggestions',
        'last_calculated_at'
    ];

    protected $casts = [
        'quality_score' => 'decimal:2',
        'quality_factors' => 'array',
        'missing_fields' => 'array',
        'validation_errors' => 'array',
        'improvement_suggestions' => 'array',
        'last_calculated_at' => 'datetime'
    ];

    // RELATIONSHIPS

    /**
     * Get the customer this quality record belongs to
     */
    public function customer()
    {
        return $this->belongsTo(CustomerLedger::class, 'customer_id', 'ledger_id');
    }

    // SCOPES

    /**
     * Scope by quality grade
     */
    public function scopeByGrade($query, $grade)
    {
        return $query->where('quality_grade', $grade);
    }

    /**
     * Scope for high quality customers
     */
    public function scopeHighQuality($query, $threshold = 80)
    {
        return $query->where('quality_score', '>=', $threshold);
    }

    /**
     * Scope for low quality customers
     */
    public function scopeLowQuality($query, $threshold = 60)
    {
        return $query->where('quality_score', '<', $threshold);
    }

    /**
     * Scope for customers needing improvement
     */
    public function scopeNeedsImprovement($query)
    {
        return $query->whereNotNull('improvement_suggestions')
            ->whereRaw('JSON_LENGTH(improvement_suggestions) > 0');
    }

    // METHODS

    /**
     * Update quality metrics
     */
    public function updateQualityMetrics($score, $factors, $missingFields = [], $validationErrors = [], $suggestions = [])
    {
        $grade = $this->calculateGrade($score);

        $this->update([
            'quality_score' => $score,
            'quality_grade' => $grade,
            'quality_factors' => $factors,
            'missing_fields' => $missingFields,
            'validation_errors' => $validationErrors,
            'improvement_suggestions' => $suggestions,
            'last_calculated_at' => now()
        ]);
    }

    /**
     * Calculate quality grade from score
     */
    private function calculateGrade($score)
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    /**
     * Get quality improvement priority
     */
    public function getImprovementPriority()
    {
        if ($this->quality_score < 50) return 'critical';
        if ($this->quality_score < 70) return 'high';
        if ($this->quality_score < 85) return 'medium';
        return 'low';
    }

    /**
     * Get quality statistics
     */
    public static function getQualityStatistics($shopId = null)
    {
        $query = static::query();

        if ($shopId) {
            $query->whereHas('customer', function ($q) use ($shopId) {
                $q->where('shop_id', $shopId);
            });
        }

        return [
            'total_customers' => $query->count(),
            'average_score' => round($query->avg('quality_score'), 2),
            'grade_distribution' => [
                'A' => $query->clone()->where('quality_grade', 'A')->count(),
                'B' => $query->clone()->where('quality_grade', 'B')->count(),
                'C' => $query->clone()->where('quality_grade', 'C')->count(),
                'D' => $query->clone()->where('quality_grade', 'D')->count(),
                'F' => $query->clone()->where('quality_grade', 'F')->count(),
            ],
            'needs_improvement' => $query->clone()->where('quality_score', '<', 70)->count(),
            'high_quality' => $query->clone()->where('quality_score', '>=', 85)->count()
        ];
    }
}
