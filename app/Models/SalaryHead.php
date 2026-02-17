<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Permission\Models\Role;

class SalaryHead extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'staff_id',
        'role_id',
        'salary_account_ledger_id',
        'salary_amount',
        'status',
        'approval_date',
        'approved_by',
        'rejection_reason',
        'created_by',
        'business_id',
    ];

    protected $casts = [
        'salary_amount' => 'decimal:2',
        'approval_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Get the user that owns the salary head
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the Spatie role assigned to this salary head
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the salary account ledger
     */
    public function salaryAccountLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'salary_account_ledger_id');
    }

    /**
     * Get the user who created this salary head
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved/rejected this salary head
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the business that owns this salary head
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the staff record associated with this salary head
     */
    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    /**
     * Scope to filter by business
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending salary heads
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved salary heads
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get rejected salary heads
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if salary head is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if salary head is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if salary head is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if salary head can be edited
     */
    public function canBeEdited(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if salary head can be deleted
     */
    public function canBeDeleted(): bool
    {
        return in_array($this->status, ['pending', 'rejected']);
    }

    /**
     * Check if salary head can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if salary head can be rejected
     */
    public function canBeRejected(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Get formatted salary amount
     */
    public function getFormattedSalaryAmountAttribute(): string
    {
        return '৳' . number_format($this->salary_amount, 2);
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get status display text
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    /**
     * Get approval status text
     */
    public function getApprovalStatusAttribute(): string
    {
        if ($this->isApproved() && $this->approvedBy) {
            return "Approved by {$this->approvedBy->name} on " . $this->approval_date->format('M d, Y');
        } elseif ($this->isRejected() && $this->approvedBy) {
            return "Rejected by {$this->approvedBy->name} on " . $this->approval_date->format('M d, Y');
        }

        return 'Pending approval';
    }
}
