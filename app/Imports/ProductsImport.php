<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ProductsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        try {
            Log::info('Starting import of row', $row);

            // Create or get category
            $category = ProductCategory::firstOrCreate(
                ['name' => $row['category_name']],
                [
                    'slug' => Str::slug($row['category_name']),
                    'status' => true,
                    'business_id' => Auth::user()->business_id
                ]
            );
            Log::info('Category processed', ['category' => $category]);

            // Calculate prices
            $dealer_price = (float)$row['dealer_price'];
            $profit_margin = (float)$row['profit_margin'];
            $trade_price = $dealer_price + ($dealer_price * ($profit_margin / 100));

            // Create product
            $product = Product::create([
                'business_id' => Auth::user()->business_id,
                'name' => $row['product_name'],
                'slug' => Str::slug($row['product_name']),
                'barcode' => $row['barcode'],
                'category_id' => $category->id,
                'unit_id' => (int)$row['unit_id'],
                'quantity_alert' => (float)$row['quantity_alert'],
                'opening_stock' => (float)$row['opening_stock'],
                'current_stock' => (float)$row['opening_stock'],
                'dealer_price' => $dealer_price,
                'trade_price' => $trade_price,
                'profit_margin' => $profit_margin,
                'tax' => $row['tax'] ?? 0,
                'status' => true
            ]);

            Log::info('Product created successfully', ['product' => $product]);
            return $product;
        } catch (\Exception $e) {
            Log::error('Import failed', [
                'error' => $e->getMessage(),
                'row' => $row
            ]);
            throw $e;
        }
    }
}
