<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductCategory;
use App\Models\UnitOfMeasurement;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\DB;

class ProductImport implements ToModel, WithHeadingRow, WithValidation
{
    private $business_id;

    public function __construct($business_id)
    {
        $this->business_id = $business_id;
    }

    public function model(array $row)
    {
        return DB::transaction(function () use ($row) {
            // Get or create category
            $category = ProductCategory::firstOrCreate(
                [
                    'name' => $row['category'],
                    'business_id' => $this->business_id
                ],
                ['status' => true]
            );

            // Get unit id based on name
            $unit = UnitOfMeasurement::where('name', $row['unit'])->first();
            if (!$unit) {
                throw new \Exception('Invalid unit name: ' . $row['unit']);
            }

            $dealerPrice = $row['dealer_price'];
            $profitMargin = $row['profit_margin'] ?? 0;
            $tradePrice = $dealerPrice + ($dealerPrice * ($profitMargin / 100));

            $product = new Product([
                'business_id' => $this->business_id,
                'name' => $row['name'],
                'barcode' => $row['barcode'] ?? time() . rand(1000, 9999),
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'quantity_alert' => $row['quantity_alert'] ?? 0,
                'opening_stock' => $row['opening_stock'] ?? 0,
                'opening_date' => $row['opening_date'] ?? now(),
                'expiry_date' => $row['expiry_date'] ?? null,
                'dealer_price' => $dealerPrice,
                'trade_price' => $tradePrice,
                'profit_margin' => $profitMargin,
                'tax' => $row['tax'] ?? 'NA',
                'image' => null,
                'status' => true
            ]);

            $product->save();

            ProductBatch::create([
                'product_id' => $product->id,
                'batch_number' => 'OB' . time() . rand(1000, 9999),
                'dealer_price' => $dealerPrice,
                'trade_price' => $tradePrice,
                'remaining_quantity' => $row['opening_stock'] ?? 0,
                'batch_date' => $row['opening_date'] ?? now(),
                'expiry_date' => $row['expiry_date'] ?? null,
                'is_opening_batch' => true
            ]);

            return $product;
        });
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'category' => 'required|string',
            'unit' => 'required|string',
            'dealer_price' => 'required|numeric|min:0',
            'profit_margin' => 'nullable|numeric|min:0|max:100',
            'opening_stock' => 'nullable|numeric|min:0',
            'opening_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:opening_date',
            'quantity_alert' => 'nullable|integer|min:0'
        ];
    }
}
