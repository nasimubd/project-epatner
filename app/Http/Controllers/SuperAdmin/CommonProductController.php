<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CommonProduct;
use App\Models\CommonCategory;
use App\Models\CommonUnit;
use Illuminate\Http\Request;
use App\Jobs\ImportCommonProductsJob;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;


class CommonProductController extends Controller
{
    /**
     * The database connection to use
     */
    protected $connection = 'mysql_common';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Simply use with() without trying to specify the connection in the closure
        $query = CommonProduct::on($this->connection)->with(['category', 'unit']);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->orderBy('product_name')->paginate(10);

        // Append all current query parameters to pagination links
        $products->appends($request->all());

        $categories = CommonCategory::on($this->connection)->orderBy('category_name')->get();

        return view('super-admin.common-products.index', compact('products', 'categories'));
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = CommonCategory::on($this->connection)->orderBy('category_name')->get();
        $units = CommonUnit::on($this->connection)->orderBy('unit_name')->get();
        return view('super-admin.common-products.create', compact('categories', 'units'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:255|unique:' . $this->connection . '.tbl_common_product,barcode',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category_id' => 'required|exists:' . $this->connection . '.tbl_common_category,category_id',
            'unit_id' => 'required|exists:' . $this->connection . '.tbl_common_unit,unit_id',
        ]);

        // Create the product without the image first
        $product = CommonProduct::on($this->connection)->create([
            'product_name' => $validated['product_name'],
            'barcode' => $validated['barcode'],
            'category_id' => $validated['category_id'],
            'unit_id' => $validated['unit_id'],
        ]);

        // Handle image separately if provided
        if ($request->hasFile('image')) {
            $image = Image::make($request->file('image'));

            if ($image->width() > 1200 || $image->height() > 1200) {
                $image->resize(1200, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Convert image to base64 string
            $imageData = base64_encode($image->encode('jpg', 40)->getEncoded());

            // Update using a parameterized query with base64 data
            DB::connection($this->connection)->update(
                "UPDATE tbl_common_product SET image = FROM_BASE64(?) WHERE product_id = ?",
                [$imageData, $product->product_id]
            );
        }

        return redirect()->route('super-admin.common-products.index')
            ->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CommonProduct $commonProduct)
    {
        $commonProduct->setConnection($this->connection);
        // Simply load the relationships without trying to specify the connection
        $commonProduct->load(['category', 'unit']);

        return view('super-admin.common-products.show', compact('commonProduct'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CommonProduct $commonProduct)
    {
        $commonProduct->setConnection($this->connection);
        $categories = CommonCategory::on($this->connection)->orderBy('category_name')->get();
        $units = CommonUnit::on($this->connection)->orderBy('unit_name')->get();
        return view('super-admin.common-products.edit', compact('commonProduct', 'categories', 'units'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CommonProduct $commonProduct)
    {
        $commonProduct->setConnection($this->connection);

        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:255|unique:' . $this->connection . '.tbl_common_product,barcode,' . $commonProduct->product_id . ',product_id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category_id' => 'required|exists:' . $this->connection . '.tbl_common_category,category_id',
            'unit_id' => 'required|exists:' . $this->connection . '.tbl_common_unit,unit_id',
        ]);

        // Update the product without the image
        $commonProduct->update([
            'product_name' => $validated['product_name'],
            'barcode' => $validated['barcode'],
            'category_id' => $validated['category_id'],
            'unit_id' => $validated['unit_id'],
        ]);

        // Handle image separately if a new one is provided
        if ($request->hasFile('image')) {
            $image = Image::make($request->file('image'));

            if ($image->width() > 1200 || $image->height() > 1200) {
                $image->resize(1200, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Convert image to base64 string
            $imageData = base64_encode($image->encode('jpg', 40)->getEncoded());

            // Update using a parameterized query with base64 data
            DB::connection($this->connection)->update(
                "UPDATE tbl_common_product SET image = FROM_BASE64(?) WHERE product_id = ?",
                [$imageData, $commonProduct->product_id]
            );
        }

        return redirect()->route('super-admin.common-products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CommonProduct $commonProduct)
    {
        $commonProduct->setConnection($this->connection);
        $commonProduct->delete();

        return redirect()->route('super-admin.common-products.index')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * Display the product image.
     */
    public function showImage(CommonProduct $commonProduct)
    {
        $commonProduct->setConnection($this->connection);

        if (!$commonProduct->image) {
            abort(404);
        }

        // For MySQL BLOB data
        $imageData = $commonProduct->image;

        // If the image is returned as a resource, get the contents
        if (is_resource($imageData)) {
            $imageData = stream_get_contents($imageData);
        }

        return response($imageData)
            ->header('Content-Type', 'image/jpeg');
    }

    /**
     * Show the import form
     */
    public function showImport()
    {
        $categories = CommonCategory::on($this->connection)->orderBy('category_name')->get();
        $units = CommonUnit::on($this->connection)->orderBy('unit_name')->get();
        return view('super-admin.common-products.import', compact('categories', 'units'));
    }

    /**
     * Process the import
     */
    public function processImport(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        // Store the uploaded file
        $file = $request->file('import_file');
        $filePath = $file->store('imports');

        if (Auth::check()) {
            // Generate a unique job ID
            $jobId = Str::uuid()->toString();

            // Store initial progress in cache
            Cache::put('import_progress_' . $jobId, [
                'progress' => 0,
                'status' => 'processing',
                'message' => 'Import started'
            ], 3600); // Cache for 1 hour

            // Dispatch job with job ID and connection name
            ImportCommonProductsJob::dispatchSync($filePath, Auth::id(), $jobId, $this->connection);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Import started',
                    'job_id' => $jobId
                ]);
            }

            return redirect()->route('super-admin.common-products.index')
                ->with('success', 'Import started. You can check progress in the system.');
        } else {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be logged in to perform this action.'
                ], 401);
            }

            return redirect()->route('super-admin.common-products.index')
                ->with('error', 'You must be logged in to perform this action.');
        }
    }


    /**
     * Get the import progress
     */
    public function getImportProgress(Request $request)
    {
        $jobId = $request->query('job_id');

        if (!$jobId) {
            return response()->json([
                'success' => false,
                'message' => 'Job ID is required'
            ], 400);
        }

        $progress = Cache::get('import_progress_' . $jobId, [
            'progress' => 0,
            'status' => 'processing',
            'message' => 'Import in progress'
        ]);

        return response()->json($progress);
    }

    /**
     * Download sample import template
     */
    public function downloadImportTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="common_products_import_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['product_name', 'barcode', 'category_id', 'unit_id', 'image_url']);
            fputcsv($file, ['Example Product', '1234567890', '1', '1', 'https://example.com/image.jpg']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
