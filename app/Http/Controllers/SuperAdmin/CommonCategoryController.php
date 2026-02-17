<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CommonCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CommonCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = CommonCategory::on('mysql_common')->orderBy('category_name')->paginate(10);
        return view('super-admin.common-categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('super-admin.common-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => 'required|string|max:255|unique:mysql_common.tbl_common_category,category_name',
        ]);

        CommonCategory::on('mysql_common')->create([
            'category_name' => $validated['category_name'],
            'slug' => Str::slug($validated['category_name']),
        ]);

        return redirect()->route('super-admin.common-categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CommonCategory $commonCategory)
    {
        // Ensure we're using the correct connection when retrieving the model
        $commonCategory->setConnection('mysql_common');

        return view('super-admin.common-categories.show', compact('commonCategory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CommonCategory $commonCategory)
    {
        // Ensure we're using the correct connection when retrieving the model
        $commonCategory->setConnection('mysql_common');

        return view('super-admin.common-categories.edit', compact('commonCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CommonCategory $commonCategory)
    {
        // Ensure we're using the correct connection
        $commonCategory->setConnection('mysql_common');

        $validated = $request->validate([
            'category_name' => 'required|string|max:255|unique:mysql_common.tbl_common_category,category_name,' . $commonCategory->category_id . ',category_id',
        ]);

        $commonCategory->update([
            'category_name' => $validated['category_name'],
            'slug' => Str::slug($validated['category_name']),
        ]);

        return redirect()->route('super-admin.common-categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CommonCategory $commonCategory)
    {
        // Ensure we're using the correct connection
        $commonCategory->setConnection('mysql_common');

        // Check if category has products
        if ($commonCategory->products()->count() > 0) {
            return redirect()->route('super-admin.common-categories.index')
                ->with('error', 'Cannot delete category with associated products.');
        }

        $commonCategory->delete();

        return redirect()->route('super-admin.common-categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
