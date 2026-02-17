<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessShopfront;
use App\Models\ShopfrontImage;
use App\Models\ProductCategory;
use App\Models\BusinessAdmin;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;

class ShopfrontImageController extends Controller
{
    public function index()
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        if (!$currentAdmin) {
            return redirect()->route('admin.dashboard')->with('error', 'Admin profile not found');
        }

        // Get the business details
        $business = Business::findOrFail($currentAdmin->business_id);

        $shopfront = BusinessShopfront::where('business_id', $currentAdmin->business_id)->first();

        if (!$shopfront) {
            return redirect()->route('admin.shopfront.index')->with('error', 'Shopfront not found. Please generate a shopfront first.');
        }

        // Get individual categories
        $categories = ProductCategory::where('business_id', $currentAdmin->business_id)
            ->where('status', true)
            ->get();

        // Get general categories (grouped by first word)
        $generalCategories = $this->getGeneralCategories($categories);

        $heroBanner = $shopfront->heroBanner;
        $categoryImages = $shopfront->categoryImages->keyBy('reference_id');
        $generalCategoryImages = $shopfront->generalCategoryImages->keyBy('reference_name');

        return view('admin.shopfront.images.index', compact(
            'shopfront',
            'categories',
            'generalCategories',
            'heroBanner',
            'categoryImages',
            'generalCategoryImages',
            'business'
        ));
    }

    private function getGeneralCategories($categories)
    {
        $generalCategories = [];

        foreach ($categories as $category) {
            $parts = preg_split('/[-\s]/', $category->name, 2);
            $generalName = trim($parts[0]);

            if (!isset($generalCategories[$generalName])) {
                $generalCategories[$generalName] = [
                    'name' => $generalName,
                    'subcategories' => []
                ];
            }

            $generalCategories[$generalName]['subcategories'][] = $category;
        }

        return $generalCategories;
    }

    public function uploadHeroBanner(Request $request)
    {
        $request->validate([
            'hero_banner' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        try {
            $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

            if (!$currentAdmin) {
                return response()->json(['success' => false, 'message' => 'Admin profile not found']);
            }

            $shopfront = BusinessShopfront::where('business_id', $currentAdmin->business_id)->first();

            if (!$shopfront) {
                return response()->json(['success' => false, 'message' => 'Shopfront not found']);
            }

            DB::beginTransaction();

            // Deactivate existing hero banner
            ShopfrontImage::where('shopfront_id', $shopfront->shopfront_id)
                ->where('image_type', ShopfrontImage::TYPE_HERO_BANNER)
                ->update(['is_active' => false]);

            // Process and optimize the image
            $file = $request->file('hero_banner');
            $image = Image::make($file);

            // Resize for hero banner (1920px width max)
            if ($image->width() > 1920 || $image->height() > 1080) {
                $image->resize(1920, 1080, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Encode with quality optimization
            $imageData = $image->encode('jpg', 85)->encoded;

            ShopfrontImage::create([
                'shopfront_id' => $shopfront->shopfront_id,
                'image_type' => ShopfrontImage::TYPE_HERO_BANNER,
                'reference_id' => null,
                'reference_name' => null,
                'image' => $imageData,
                'image_name' => $file->getClientOriginalName(),
                'mime_type' => 'image/jpeg',
                'image_size' => strlen($imageData),
                'is_active' => true
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hero banner uploaded successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Hero banner upload failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload hero banner: ' . $e->getMessage()
            ]);
        }
    }

    public function uploadCategoryImage(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'category_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:3072', // 3MB max
        ]);

        try {
            $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

            if (!$currentAdmin) {
                return response()->json(['success' => false, 'message' => 'Admin profile not found']);
            }

            $shopfront = BusinessShopfront::where('business_id', $currentAdmin->business_id)->first();

            if (!$shopfront) {
                return response()->json(['success' => false, 'message' => 'Shopfront not found']);
            }

            // Verify category belongs to business
            $category = ProductCategory::where('id', $request->category_id)
                ->where('business_id', $currentAdmin->business_id)
                ->first();

            if (!$category) {
                return response()->json(['success' => false, 'message' => 'Category not found']);
            }

            DB::beginTransaction();

            // Deactivate existing category image
            ShopfrontImage::where('shopfront_id', $shopfront->shopfront_id)
                ->where('image_type', ShopfrontImage::TYPE_CATEGORY)
                ->where('reference_id', $request->category_id)
                ->update(['is_active' => false]);

            // Process and optimize the image
            $file = $request->file('category_image');
            $image = Image::make($file);

            // Resize for category image (800px width max)
            if ($image->width() > 800 || $image->height() > 600) {
                $image->resize(800, 600, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Encode with quality optimization
            $imageData = $image->encode('jpg', 80)->encoded;

            ShopfrontImage::create([
                'shopfront_id' => $shopfront->shopfront_id,
                'image_type' => ShopfrontImage::TYPE_CATEGORY,
                'reference_id' => $request->category_id,
                'reference_name' => null,
                'image' => $imageData,
                'image_name' => $file->getClientOriginalName(),
                'mime_type' => 'image/jpeg',
                'image_size' => strlen($imageData),
                'is_active' => true
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category image uploaded successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Category image upload failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload category image: ' . $e->getMessage()
            ]);
        }
    }

    public function uploadGeneralCategoryImage(Request $request)
    {
        $request->validate([
            'general_category_name' => 'required|string|max:255',
            'general_category_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:3072', // 3MB max
        ]);

        try {
            $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

            if (!$currentAdmin) {
                return response()->json(['success' => false, 'message' => 'Admin profile not found']);
            }

            $shopfront = BusinessShopfront::where('business_id', $currentAdmin->business_id)->first();

            if (!$shopfront) {
                return response()->json(['success' => false, 'message' => 'Shopfront not found']);
            }

            DB::beginTransaction();

            // Deactivate existing general category image
            ShopfrontImage::where('shopfront_id', $shopfront->shopfront_id)
                ->where('image_type', ShopfrontImage::TYPE_GENERAL_CATEGORY)
                ->where('reference_name', $request->general_category_name)
                ->update(['is_active' => false]);

            // Process and optimize the image
            $file = $request->file('general_category_image');
            $image = Image::make($file);

            // Resize for general category image (800px width max)
            if ($image->width() > 800 || $image->height() > 600) {
                $image->resize(800, 600, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Encode with quality optimization
            $imageData = $image->encode('jpg', 80)->encoded;

            ShopfrontImage::create([
                'shopfront_id' => $shopfront->shopfront_id,
                'image_type' => ShopfrontImage::TYPE_GENERAL_CATEGORY,
                'reference_id' => null,
                'reference_name' => $request->general_category_name,
                'image' => $imageData,
                'image_name' => $file->getClientOriginalName(),
                'mime_type' => 'image/jpeg',
                'image_size' => strlen($imageData),
                'is_active' => true
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'General category image uploaded successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('General category image upload failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload general category image: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteImage(Request $request)
    {
        $request->validate([
            'image_id' => 'required|exists:shopfront_images,id',
        ]);

        try {
            $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

            if (!$currentAdmin) {
                return response()->json(['success' => false, 'message' => 'Admin profile not found']);
            }

            $shopfront = BusinessShopfront::where('business_id', $currentAdmin->business_id)->first();

            if (!$shopfront) {
                return response()->json(['success' => false, 'message' => 'Shopfront not found']);
            }

            $image = ShopfrontImage::where('id', $request->image_id)
                ->where('shopfront_id', $shopfront->shopfront_id)
                ->first();

            if (!$image) {
                return response()->json(['success' => false, 'message' => 'Image not found']);
            }

            $image->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Image deletion failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage()
            ]);
        }
    }
}
