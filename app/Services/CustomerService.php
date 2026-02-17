<?php

namespace App\Services;

use App\Models\Ledger;
use App\Models\BusinessSubDistrict;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerService
{
    /**
     * Get customers from common database for a business
     */
    public function getCustomersForBusiness($businessId)
    {
        try {
            // Get business sub-districts
            $subDistricts = BusinessSubDistrict::where('business_id', $businessId)
                ->active()
                ->get(['district', 'sub_district']);

            if ($subDistricts->isEmpty()) {
                Log::info("No sub-districts configured for business: {$businessId}");
                return collect();
            }

            $customers = collect();

            foreach ($subDistricts as $subDistrict) {
                $commonCustomers = DB::connection('mysql_common')
                    ->table('tbl_defult_customers')
                    ->where('district', $subDistrict->district)
                    ->where('sub_district', $subDistrict->sub_district)
                    ->whereNotNull('ledger_name')
                    ->where('ledger_name', '!=', '')
                    ->select([
                        'customer_id as id',
                        'ledger_name as name',
                        'village',
                        'sub_district',
                        'district',
                        'contact_number as contact'
                    ])
                    ->get();

                $customers = $customers->merge($commonCustomers);
            }

            // Format customers with full location
            return $customers->map(function ($customer) {
                $locationParts = array_filter([
                    $customer->village,
                    $customer->sub_district,
                    $customer->district
                ]);

                $customer->location = implode(', ', $locationParts);
                $customer->display_name = $customer->name . ($customer->location ? ' - ' . $customer->location : '');

                return $customer;
            })->sortBy('name');
        } catch (\Exception $e) {
            Log::error('Error fetching customers for business: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get unique villages for business
     */
    public function getVillagesForBusiness($businessId)
    {
        return BusinessSubDistrict::getVillagesForBusiness($businessId);
    }

    /**
     * Create or get local customer copy
     */
    public function createOrGetLocalCustomer($commonCustomerId, $businessId)
    {
        try {
            // Check if local copy already exists
            $existingLedger = Ledger::where('business_id', $businessId)
                ->where('common_customer_id', $commonCustomerId)
                ->where('ledger_type', 'Sundry Debtors (Customer)')
                ->first();

            if ($existingLedger) {
                return $existingLedger;
            }

            // Get customer data from common database
            $commonCustomer = DB::connection('mysql_common')
                ->table('tbl_defult_customers')
                ->where('customer_id', $commonCustomerId)
                ->first();

            if (!$commonCustomer) {
                throw new \Exception("Common customer not found: {$commonCustomerId}");
            }

            // Create location string
            $locationParts = array_filter([
                $commonCustomer->village,
                $commonCustomer->sub_district,
                $commonCustomer->district
            ]);
            $location = implode(', ', $locationParts);

            // Create local ledger copy
            $ledger = Ledger::create([
                'business_id' => $businessId,
                'name' => $commonCustomer->ledger_name,
                'ledger_type' => 'Sundry Debtors (Customer)',
                'opening_balance' => 0,
                'balance_type' => 'debit',
                'location' => $location,
                'contact' => $commonCustomer->contact_number,
                'common_customer_id' => $commonCustomerId,
                'status' => 'active'
            ]);

            Log::info("Created local customer copy", [
                'ledger_id' => $ledger->id,
                'common_customer_id' => $commonCustomerId,
                'business_id' => $businessId
            ]);

            return $ledger;
        } catch (\Exception $e) {
            Log::error('Error creating local customer copy: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Filter customers by village
     */
    public function filterCustomersByVillage($customers, $village)
    {
        if (!$village) {
            return $customers;
        }

        return $customers->filter(function ($customer) use ($village) {
            return $customer->village === $village;
        });
    }
}
