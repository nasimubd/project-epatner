<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessShopfront;
use App\Models\Business;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShopfrontController extends Controller
{
    public function show($id)
    {
        try {
            // Get shopfront with minimal relations
            $shopfront = BusinessShopfront::where('shopfront_id', $id)
                ->where('is_active', true)
                ->with(['business:id,name', 'heroBanner:id,shopfront_id,image,image_name,is_active'])
                ->firstOrFail();

            $businessId = $shopfront->business_id;

            // Load category images directly from database
            $categoryImages = DB::table('shopfront_images')
                ->where('shopfront_id', $id)
                ->where('is_active', 1)
                ->whereIn('image_type', ['category', 'general_category'])
                ->select('id', 'image_type', 'reference_id', 'reference_name', 'image', 'image_name')
                ->get();

            // Debug: Log category images
            Log::info('Category images loaded directly', [
                'shopfront_id' => $id,
                'images_count' => $categoryImages->count(),
                'images_data' => $categoryImages->map(function ($img) {
                    return [
                        'id' => $img->id,
                        'image_type' => $img->image_type,
                        'reference_id' => $img->reference_id,
                        'reference_name' => $img->reference_name,
                        'has_image' => !empty($img->image),
                        'image_size' => $img->image ? strlen($img->image) : 0
                    ];
                })->toArray()
            ]);

            // Get categories efficiently
            $categories = ProductCategory::where('business_id', $businessId)
                ->where('status', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            // Get products with only necessary data
            $products = Product::with([
                'category:id,name',
                'batches' => function ($query) {
                    $query->where('remaining_quantity', '>', 0)
                        ->orderBy('batch_date', 'asc')
                        ->select('id', 'product_id', 'batch_number', 'trade_price', 'remaining_quantity', 'expiry_date');
                }
            ])
                ->where('business_id', $businessId)
                ->where('current_stock', '>', 0)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('product_batches')
                        ->whereRaw('product_batches.product_id = products.id')
                        ->where('remaining_quantity', '>', 0);
                })
                ->select('id', 'name', 'category_id', 'trade_price', 'current_stock', 'barcode', 'image')
                ->orderBy('name')
                ->get();

            // Group products by categories efficiently
            $generalCategories = $this->groupProductsByCategories($categories, $products);

            // Prepare category images for easy access
            $categoryImagesArray = [];

            foreach ($categoryImages as $image) {
                if ($image->image_type === 'category' && $image->reference_id) {
                    // Specific category image (by category ID)
                    $categoryImagesArray[$image->reference_id] = $image;
                } elseif ($image->image_type === 'general_category' && $image->reference_name) {
                    // General category image (by category name)
                    $categoryImagesArray[$image->reference_name] = $image;
                }
            }

            // Debug: Log final category images array
            Log::info('Final category images', [
                'category_images_keys' => array_keys($categoryImagesArray),
                'total_images' => count($categoryImagesArray)
            ]);

            return view('shopfront.index', [
                'shopfront' => $shopfront,
                'business' => $shopfront->business,
                'generalCategories' => $generalCategories,
                'heroBanner' => $shopfront->heroBanner,
                'categoryImages' => $categoryImagesArray
            ]);
        } catch (\Exception $e) {
            Log::error('Shopfront error: ' . $e->getMessage());
            return view('errors.shopfront', ['error' => 'Store not found or unavailable']);
        }
    }




    /**
     * Group products by general categories
     */
    private function groupProductsByCategories($categories, $products)
    {
        $generalCategories = [];
        $productsByCategory = $products->groupBy('category_id');

        foreach ($categories as $category) {
            $parts = preg_split('/[-\s]/', $category->name, 2);
            $generalName = trim($parts[0]);

            if (!isset($generalCategories[$generalName])) {
                $generalCategories[$generalName] = [
                    'name' => $generalName,
                    'subcategories' => [],
                    'products' => []
                ];
            }

            $generalCategories[$generalName]['subcategories'][] = [
                'id' => $category->id,
                'name' => $category->name
            ];

            // Add products for this category
            $categoryProducts = $productsByCategory->get($category->id, collect());
            foreach ($categoryProducts as $product) {
                $productArray = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category_id' => $product->category_id,
                    'trade_price' => $product->trade_price,
                    'current_stock' => $product->current_stock,
                    'barcode' => $product->barcode,
                    'image' => $product->image ? base64_encode($product->image) : null,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name
                    ] : null,
                    'batches' => []
                ];

                // Add batches
                foreach ($product->batches as $batch) {
                    $productArray['batches'][] = [
                        'id' => $batch->id,
                        'product_id' => $batch->product_id,
                        'batch_number' => $batch->batch_number,
                        'trade_price' => $batch->trade_price,
                        'remaining_quantity' => $batch->remaining_quantity,
                        'expiry_date' => $batch->expiry_date
                    ];
                }

                $generalCategories[$generalName]['products'][] = $productArray;
            }
        }

        // Remove empty categories and sort
        $generalCategories = array_filter($generalCategories, function ($category) {
            return !empty($category['products']);
        });

        ksort($generalCategories);

        // Sort products within each category
        foreach ($generalCategories as &$category) {
            usort($category['products'], function ($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });
        }

        return $generalCategories;
    }

    /**
     * Display the cart page
     */
    public function cart($id)
    {
        $shopfront = BusinessShopfront::where('shopfront_id', $id)
            ->where('is_active', true)
            ->with('business:id,name')
            ->firstOrFail();

        return view('shopfront.cart', [
            'shopfront' => $shopfront,
            'business' => $shopfront->business
        ]);
    }

    /**
     * Get product images for a category via AJAX
     */
    public function getCategoryProducts($shopfrontId, $categoryKey)
    {
        try {
            $shopfront = BusinessShopfront::where('shopfront_id', $shopfrontId)
                ->where('is_active', true)
                ->firstOrFail();

            // Get fresh data for the requested category
            $categories = ProductCategory::where('business_id', $shopfront->business_id)
                ->where('status', true)
                ->where('name', 'LIKE', $categoryKey . '%')
                ->pluck('id');

            $products = Product::with([
                'category:id,name',
                'batches' => function ($query) {
                    $query->where('remaining_quantity', '>', 0)
                        ->select('id', 'product_id', 'batch_number', 'trade_price', 'remaining_quantity', 'expiry_date');
                }
            ])
                ->where('business_id', $shopfront->business_id)
                ->whereIn('category_id', $categories)
                ->where('current_stock', '>', 0)
                ->select('id', 'name', 'category_id', 'trade_price', 'current_stock', 'image')
                ->get();

            $productsArray = [];
            foreach ($products as $product) {
                $productsArray[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'trade_price' => $product->trade_price,
                    'current_stock' => $product->current_stock,
                    'image' => $product->image ? base64_encode($product->image) : null,
                    'batches' => $product->batches->toArray()
                ];
            }

            return response()->json([
                'products' => $productsArray,
                'category_name' => $categoryKey
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching category products: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load products'], 500);
        }
    }
}
