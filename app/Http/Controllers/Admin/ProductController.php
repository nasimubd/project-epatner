<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\UnitOfMeasurement;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductImport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\BusinessAdmin;
use App\Models\ProductBatch;
use Intervention\Image\Facades\Image;

class ProductController extends Controller
{
    /**
     * Show the list of products.
     *
     * If the user is a staff, products are filtered by the categories assigned to the staff.
     * Otherwise, products are filtered by the business of the current admin.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        // Cache key based on request parameters and user
        $cacheKey = sprintf(
            'products_list_%s_%s_%s_%s_%s',
            $currentAdmin->business_id,
            $request->get('search', ''),
            $request->get('category', ''),
            $request->get('page', 1),
            Cache::get('products_last_updated', now()->timestamp)
        );

        $products = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request, $currentAdmin) {
            $query = Product::with([
                'category:id,name,status',
                'unit:id,name,type',
                'batches' => function ($query) {
                    $query->select('id', 'product_id', 'remaining_quantity');
                }
            ])
                ->select([
                    'id',
                    'name',
                    'barcode',
                    'category_id',
                    'unit_id',
                    'current_stock',
                    'quantity_alert',
                    'updated_at',
                    'business_id'
                ]);

            // Apply search filter
            if ($request->filled('search')) {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', $searchTerm)
                        ->orWhere('barcode', 'like', $searchTerm);
                });
            }

            // Apply category filter
            if ($request->filled('category')) {
                $query->where('category_id', $request->category);
            }

            // Staff permission check
            if (Auth::user()->roles->contains('name', 'staff')) {
                $categoryIds = DB::table('category_staff')
                    ->join('product_categories', 'category_staff.product_category_id', '=', 'product_categories.id')
                    ->where('category_staff.user_id', Auth::id())
                    ->where('product_categories.status', true)
                    ->where('product_categories.business_id', $currentAdmin->business_id)
                    ->pluck('product_categories.id');

                $query->whereIn('category_id', $categoryIds);
            } else {
                $query->where('business_id', $currentAdmin->business_id);
            }

            return $query->latest('updated_at')->paginate(10)->withQueryString();
        });

        $categories = Cache::remember(
            'categories_' . $currentAdmin->business_id,
            now()->addHours(24),
            function () use ($currentAdmin) {
                return ProductCategory::where('business_id', $currentAdmin->business_id)
                    ->where('status', true)
                    ->select('id', 'name')
                    ->get();
            }
        );

        return view('admin.inventory.products.index', compact('products', 'categories'));
    }




    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        $categories = ProductCategory::where('business_id', $currentAdmin->business_id)
            ->where('status', true)
            ->get();

        $units = UnitOfMeasurement::where('status', true)->get();

        return view('admin.inventory.products.create', compact('categories', 'units'));
    }


    /**
     * Store a newly created product in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        // Get the unit first
        $unit = UnitOfMeasurement::findOrFail($request->unit_id);

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'barcode'        => 'required|string|unique:products',
            'category_id'    => 'required|exists:product_categories,id',
            'unit_id'        => 'required|exists:unit_of_measurements,id',
            'quantity_alert' => 'required|integer|min:0',
            'opening_date'   => 'required|date',
            'expiry_date'    => 'nullable|date|after:opening_date',
            'dealer_price'   => 'required|numeric|min:0',
            'trade_price'    => 'required|numeric|min:0',
            'profit_margin'  => 'required|numeric|min:0|max:100',
            'tax'            => 'nullable|string',
            'image'          => 'nullable|image|max:2048',
            'current_stock'  => 'nullable|numeric',
            'opening_stock'  => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($unit) {
                    if ($unit->type !== 'fraction' && floor($value) != $value) {
                        $fail('The opening stock must be a whole number for this unit type.');
                    }
                    if ($unit->type === 'fraction') {
                        // Limit to 3 decimal places without rounding
                        $decimalPart = explode('.', $value)[1] ?? '';
                        if (strlen($decimalPart) > 3) {
                            $fail('The opening stock may not have more than 3 decimal places for fractional units.');
                        }
                    }
                },
            ],
        ]);

        return DB::transaction(function () use ($validated, $request, $currentAdmin) {

            $dealerPrice  = $validated['dealer_price'];
            $profitMargin = $validated['profit_margin'];
            $tradePrice   = $dealerPrice + ($dealerPrice * ($profitMargin / 100));

            if ($request->hasFile('image')) {
                $image = Image::make($request->file('image'));

                // If image is larger than 1200px in either dimension
                if ($image->width() > 1200 || $image->height() > 1200) {
                    $image->resize(1200, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }

                $validated['image'] = $image->encode('jpg', 40)->encoded;
            }


            $validated['business_id'] = $currentAdmin->business_id;
            $validated['trade_price'] = $tradePrice;
            $validated['current_stock'] = $validated['opening_stock'];

            $product = Product::create($validated);

            ProductBatch::create([
                'product_id'         => $product->id,
                'batch_number'       => 'OB' . time() . rand(1000, 9999),
                'dealer_price'       => $validated['dealer_price'],
                'trade_price'        => $tradePrice,
                'remaining_quantity' => $validated['opening_stock'],
                'batch_date'         => $validated['opening_date'],
                'expiry_date'        => $validated['expiry_date'],
                'is_opening_batch'   => true
            ]);
            // In store method after successful creation
            Cache::put('products_last_updated', now()->timestamp);

            return redirect()->route('admin.inventory.products.index')
                ->with('success', 'Product created successfully');
        });
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        $categories = ProductCategory::where('business_id', $currentAdmin->business_id)
            ->where('status', true)
            ->get();

        $units = UnitOfMeasurement::where('status', true)->get();

        return view('admin.inventory.products.edit', compact('product', 'categories', 'units'));
    }


    public function update(Request $request, Product $product)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        if ($product->business_id !== $currentAdmin->business_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
            'category_id' => 'nullable|exists:product_categories,id',
            'unit_id' => 'nullable|exists:unit_of_measurements,id',
            'quantity_alert' => 'nullable|integer|min:0',
            'opening_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:opening_date',
            'dealer_price' => 'nullable|numeric|min:0',
            'profit_margin' => 'nullable|numeric|min:0|max:100',
            'tax' => 'nullable|string',
            'image' => 'nullable|image|max:2048'
        ]);

        return DB::transaction(function () use ($validated, $request, $product) {
            // Only update fields that were provided
            $updateData = array_filter($validated, function ($value) {
                return $value !== null;
            });

            // Calculate trade price if dealer price or profit margin was updated
            if (isset($updateData['dealer_price']) || isset($updateData['profit_margin'])) {
                $dealerPrice = $updateData['dealer_price'] ?? $product->dealer_price;
                $profitMargin = $updateData['profit_margin'] ?? $product->profit_margin;
                $updateData['trade_price'] = $dealerPrice + ($dealerPrice * ($profitMargin / 100));
            }

            // Handle image update if provided
            if ($request->hasFile('image')) {
                $image = Image::make($request->file('image'));
                if ($image->width() > 1200 || $image->height() > 1200) {
                    $image->resize(1200, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }
                $updateData['image'] = $image->encode('jpg', 40)->encoded;
            }

            $product->update($updateData);
            // In update method after successful update
            Cache::put('products_last_updated', now()->timestamp);


            return redirect()->route('admin.inventory.products.index')
                ->with('success', 'Product updated successfully');
        });
    }



    /**
     * Remove the specified product from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */

    public function destroy(Product $product)
    {
        $product->delete();

        // In destroy method after successful deletion
        Cache::put('products_last_updated', now()->timestamp);

        return redirect()->route('admin.inventory.products.index')
            ->with('success', 'Product deleted successfully');
    }


    /**
     * Import products from an uploaded Excel file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);

        DB::beginTransaction();
        try {
            $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

            if (Auth::user()->roles->contains('name', 'staff')) {
                $allowedCategories = DB::table('category_staff')
                    ->where('user_id', Auth::id())
                    ->where('business_id', $currentAdmin->business_id)
                    ->pluck('product_category_id')
                    ->toArray();

                if (empty($allowedCategories)) {
                    return redirect()->back()->with('error', 'You do not have permission to import products');
                }
            }

            Excel::import(new ProductImport($currentAdmin->business_id), $request->file('file'));

            DB::commit();
            return redirect()->route('admin.inventory.products.index')
                ->with('success', 'Products imported successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error importing products: ' . $e->getMessage());
        }
    }

    /**
     * Downloads a sample template for importing products.
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="product_import_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // Write available units reference
            $units = UnitOfMeasurement::where('status', true)
                ->pluck('name')
                ->toArray();

            fputcsv($file, ['Available Units: ' . implode(', ', $units)]);
            fputcsv($file, ['']); // Empty line as separator

            // Template headers
            fputcsv($file, [
                'name',
                'barcode',
                'category',
                'unit',
                'quantity_alert',
                'opening_stock',
                'opening_date',
                'expiry_date',
                'dealer_price',
                'profit_margin',
                'tax'
            ]);

            // Sample data
            fputcsv($file, [
                'Sample Product',
                '1234567890',
                'Electronics',
                'unit',
                '10',
                '100',
                date('Y-m-d'),
                date('Y-m-d', strtotime('+1 year')),
                '100.00',
                '20',
                'NA'
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate a unique 10-digit barcode for a product.
     *
     * This function generates a random 10-digit number and ensures that
     * it is unique by checking against existing barcodes in the database.
     * The generated barcode is returned as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function generateBarcode()
    {
        do {
            $barcode = mt_rand(1000000000, 9999999999);
        } while (Product::where('barcode', $barcode)->exists());

        return response()->json(['barcode' => $barcode]);
    }
}
