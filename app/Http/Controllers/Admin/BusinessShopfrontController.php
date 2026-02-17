<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessAdmin;
use App\Models\BusinessShopfront;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class BusinessShopfrontController extends Controller
{
    /**
     * Display the shopfront details for the current business.
     */
    public function index()
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        // Get the business details
        $business = Business::findOrFail($currentAdmin->business_id);

        $shopfront = BusinessShopfront::where('business_id', $currentAdmin->business_id)
            ->first();

        $shopfrontUrl = $shopfront ? route('shopfront.show', ['id' => $shopfront->shopfront_id]) : null;

        return view('admin.shopfront.index', compact('shopfront', 'shopfrontUrl', 'business'));
    }

    /**
     * Generate or regenerate a shopfront for the current business.
     */
    public function generate(Request $request)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        // Generate unique shopfront identifier
        $shopfrontId = Str::random(12);

        // Create or update shopfront record
        $shopfront = BusinessShopfront::updateOrCreate(
            ['business_id' => $currentAdmin->business_id],
            [
                'shopfront_id' => $shopfrontId,
                'is_active' => true
            ]
        );

        // Generate QR code
        $shopfrontUrl = route('shopfront.show', ['id' => $shopfrontId]);
        $qrCode = QrCode::size(300)->generate($shopfrontUrl);

        // Store QR code
        $shopfront->qr_code = $qrCode;
        $shopfront->save();

        return redirect()->route('admin.shopfront.index')
            ->with('success', 'Shopfront QR code generated successfully');
    }

    /**
     * Toggle the active status of the shopfront.
     */
    public function toggleStatus(Request $request)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        $shopfront = BusinessShopfront::where('business_id', $currentAdmin->business_id)
            ->firstOrFail();

        $shopfront->is_active = !$shopfront->is_active;
        $shopfront->save();

        $status = $shopfront->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.shopfront.index')
            ->with('success', "Shopfront {$status} successfully");
    }

    /**
     * Get the business name for the current admin user
     */
    public function getBusinessName()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'business_name' => 'Business'
                ]);
            }

            // Check if user is admin and get business through business_admins table
            $businessAdmin = \App\Models\BusinessAdmin::where('user_id', $user->id)->first();

            if ($businessAdmin && $businessAdmin->business) {
                return response()->json([
                    'success' => true,
                    'business_name' => $businessAdmin->business->name
                ]);
            }

            // Fallback: try to get business directly if user has business_id
            if (isset($user->business_id)) {
                $business = \App\Models\Business::find($user->business_id);
                if ($business) {
                    return response()->json([
                        'success' => true,
                        'business_name' => $business->name
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'business_name' => 'Business'
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching business name: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'business_name' => 'Business'
            ]);
        }
    }
}
