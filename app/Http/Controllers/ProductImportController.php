<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductsImport;
use App\Imports\CategoriesImport;

class ProductImportController extends Controller
{
    // Display the product import form
    public function index()
    {
        return view('admin.inventory.products.import');
    }

    // Handle the CSV file upload and import
    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);

        try {
            Excel::import(new ProductsImport, $request->file('file'));
            return redirect()->route('admin.inventory.products.index')
                ->with('success', 'Products imported successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    // Download the sample CSV file for import
    public function downloadSample()
    {
        $filePath = storage_path('app/sample-product-import.csv');
        return response()->download($filePath);
    }
}
