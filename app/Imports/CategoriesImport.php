<?php

namespace App\Imports;

use App\Models\ProductCategory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CategoriesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new ProductCategory([
            'business_id' => Auth::user()->business_id, // or adjust accordingly
            'name' => $row['category_name'], // adjust based on CSV column name
            'slug' => Str::slug($row['category_name']),
        ]);
    }
}
