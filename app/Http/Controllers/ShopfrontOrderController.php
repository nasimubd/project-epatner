<?php

namespace App\Http\Controllers;

use App\Models\ShopfrontOrder;
use App\Models\ShopfrontOrderLine;
use App\Models\Product;
use App\Models\BusinessShopfront;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Business;
use Illuminate\Support\Facades\Log;
use App\Models\ProductBatch;


class ShopfrontOrderController extends Controller
{
    public function store(Request $request, $shopfrontId)
    {
        DB::beginTransaction();
        try {
            // Validate request
            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:20',
                'location' => 'required|string|max:255',
                'products' => 'required|array',
                'products.*.id' => 'required|integer|exists:products,id',
                'products.*.quantity' => 'required|numeric|min:0.01',
                'products.*.batch_id' => 'required|exists:product_batches,id'
            ]);

            // Find shopfront
            $shopfront = BusinessShopfront::where('shopfront_id', $shopfrontId)
                ->where('is_active', true)
                ->firstOrFail();

            // Create base order
            $order = ShopfrontOrder::create([
                'business_id' => $shopfront->business_id,
                'order_number' => 'SO-' . date('Ymd') . '-' . Str::random(5),
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'location' => $validated['location'],
                'status' => 'pending',
                'total_amount' => 0
            ]);

            $totalAmount = 0;

            // Process each product line
            foreach ($validated['products'] as $line) {
                $product = Product::findOrFail($line['id']);
                $batch = ProductBatch::where('id', $line['batch_id'])
                    ->where('remaining_quantity', '>=', $line['quantity'])
                    ->firstOrFail();

                $lineTotal = $batch->trade_price * $line['quantity'];

                // Create order line
                ShopfrontOrderLine::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'batch_id' => $batch->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $batch->trade_price,
                    'line_total' => $lineTotal
                ]);

                // Update stock
                $batch->remaining_quantity -= $line['quantity'];
                $batch->save();

                $product->current_stock -= $line['quantity'];
                $product->save();

                $totalAmount += $lineTotal;
            }

            // Update order total
            $order->total_amount = $totalAmount;
            $order->invoice_number = 'INV-' . date('Ymd') . '-' . Str::random(5);
            $order->save();

            DB::commit();

            // ADD THIS LINE RIGHT AFTER DB::commit():
            $this->clearRelatedShopfrontCache($shopfront->business_id);

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'order_number' => $order->order_number,
                'redirect_url' => route('shopfront.invoice', [
                    'id' => $shopfront->shopfront_id,
                    'orderNumber' => $order->order_number
                ])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order Failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Order failed: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Clear shopfront cache after inventory changes
     */
    private function clearRelatedShopfrontCache($businessId)
    {
        try {
            // Find all shopfronts for this business
            $shopfronts = \App\Models\BusinessShopfront::where('business_id', $businessId)
                ->where('is_active', true)
                ->get();

            // Clear cache for each shopfront
            foreach ($shopfronts as $shopfront) {
                $cacheKey = "shopfront_{$shopfront->shopfront_id}";
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
            }

            Log::info('Cleared shopfront cache after order placement', [
                'business_id' => $businessId,
                'shopfronts_cleared' => $shopfronts->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear shopfront cache: ' . $e->getMessage());
        }
    }



    /**
     * Display the invoice for an order.
     */
    public function showInvoice($shopfrontId, $orderNumber)
    {
        $shopfront = BusinessShopfront::where('shopfront_id', $shopfrontId)
            ->where('is_active', true)
            ->firstOrFail();

        $order = ShopfrontOrder::with('orderLines')
            ->where('order_number', $orderNumber)
            ->where('business_id', $shopfront->business_id)
            ->firstOrFail();

        $business = Business::findOrFail($shopfront->business_id);

        return view('shopfront.invoice', compact('order', 'business', 'shopfront'));
    }
}
