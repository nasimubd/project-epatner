<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Staff extends Model
{

    protected $fillable = [
        'user_id',
        'business_id',
        'disable_underprice',
        'phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function productCategories()
    {

        return $this->belongsToMany(ProductCategory::class, 'staff_product_categories');
    }

    public function ledgers()
    {
        return $this->belongsToMany(Ledger::class, 'staff_ledgers');
    }

    public function hasPermissionTo($permission)
    {
        // You can add custom logic or use Spatie's built-in method
        return $this->user->hasPermissionTo($permission);
    }

    public function categories()
    {
        return $this->belongsToMany(ProductCategory::class, 'staff_categories', 'staff_id', 'category_id');
    }

    public function hasAccessToProducts()
    {
        // Check if staff has any assigned product categories
        return $this->productCategories()->exists();
    }

    public function getAccessibleProducts()
    {
        // Get product categories assigned to the staff
        $categoryIds = $this->productCategories->pluck('id');

        // Fetch products in those categories for the staff's business
        return Product::whereIn('category_id', $categoryIds)
            ->where('business_id', $this->business_id)
            ->get();
    }

    protected $casts = [
        'disable_underprice' => 'boolean',
    ];

    // DSR to Staff assignments (DSR can be assigned to multiple staff)
    public function assignedStaffMembers()
    {
        return $this->belongsToMany(Staff::class, 'dsr_staff_assignments', 'dsr_staff_id', 'assigned_staff_id')
            ->withTimestamps();
    }

    // Staff to DSR assignments (Staff can have multiple DSRs)
    public function assignedDsrs()
    {
        return $this->belongsToMany(Staff::class, 'dsr_staff_assignments', 'assigned_staff_id', 'dsr_staff_id')
            ->withTimestamps();
    }






    // Check if this staff member is a DSR
    public function isDsr()
    {
        return $this->user->hasRole('dsr');
    }

    // Get all staff members this DSR is assigned to deliver for
    public function getAssignedStaffMembers()
    {
        if (!$this->isDsr()) {
            return collect();
        }
        return $this->assignedStaffMembers;
    }

    // Get all DSRs assigned to this staff member
    public function getAssignedDsrs()
    {
        return $this->assignedDsrs;
    }

    // Check if this DSR is assigned to a specific staff member
    public function isAssignedToStaff($staffId)
    {
        if (!$this->isDsr()) {
            return false;
        }
        return $this->assignedStaffMembers()->where('staff.id', $staffId)->exists();
    }

    // Check if this staff member has a specific DSR assigned
    public function hasDsrAssigned($dsrId)
    {
        return $this->assignedDsrs()->where('staff.id', $dsrId)->exists();
    }
}
