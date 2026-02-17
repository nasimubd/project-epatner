<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\BusinessAdmin;
use Illuminate\Support\Facades\DB;
use App\Models\Ledger;
use Illuminate\Support\Facades\Log;
use App\Models\CommonCategory;

class CategoryController extends Controller
{
    public function index()
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        $categories = ProductCategory::where('business_id', $currentAdmin->business_id)
            ->paginate(10);

        return view('admin.inventory.categories.index', compact('categories'));
    }

    public function create()
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        // Fetch ALL ledgers for the current business, without filtering
        //$ledgers = Ledger::where('business_id', $currentAdmin->business_id)->get();
        $ledgers = Ledger::where('business_id', $currentAdmin->business_id)
            ->where('ledger_type', 'Sundry Creditors (Supplier)')
            ->get();

        return view('admin.inventory.categories.create', compact('ledgers'));
    }

    public function store(Request $request)
    {

        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_categories', 'name')->where(function ($query) use ($currentAdmin) {
                    return $query->where('business_id', $currentAdmin->business_id);
                })
            ],
            'ledger_id' => [
                'required',
                'exists:ledgers,id',
                function ($attribute, $value, $fail) use ($currentAdmin) {
                    // Step 3: Inspect the selected ledger details
                    $ledger = Ledger::where('id', $value)
                        ->where('business_id', $currentAdmin->business_id)
                        ->first();
                    if (!$ledger) {
                        $fail('Selected ledger is invalid.');
                    }
                }
            ],
            'status' => 'required|boolean'
        ]);



        // Generate a unique slug by appending the business_id
        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug . '-' . $currentAdmin->business_id;

        $category = ProductCategory::create([
            'name' => $validated['name'],
            'status' => $validated['status'],
            'business_id' => $currentAdmin->business_id,
            'ledger_id' => $validated['ledger_id'],
            'slug' => $slug
        ]);

        return redirect()->route('admin.inventory.categories.index')
            ->with('success', 'Category created successfully');
    }



    public function edit(ProductCategory $category)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        if ($category->business_id !== $currentAdmin->business_id) {
            abort(403);
        }

        // Fetch supplier ledgers for the current business
        $ledgers = Ledger::where('business_id', $currentAdmin->business_id)
            ->where('ledger_type', 'Sundry Creditors (Supplier)')
            ->get();

        return view('admin.inventory.categories.edit', compact('category', 'ledgers'));
    }

    public function update(Request $request, ProductCategory $category)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        if ($category->business_id !== $currentAdmin->business_id) {
            return redirect()->back()->with('error', 'You do not have permission to modify this category');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_categories', 'name')
                    ->where('business_id', $currentAdmin->business_id)
                    ->ignore($category->id)
            ],
            'ledger_id' => [
                'required',
                'exists:ledgers,id',
                function ($attribute, $value, $fail) use ($currentAdmin) {
                    $ledger = Ledger::where('id', $value)
                        ->where('business_id', $currentAdmin->business_id)
                        ->where('ledger_type', 'Sundry Creditors (Supplier)')
                        ->first();

                    if (!$ledger) {
                        $fail('Selected ledger is invalid.');
                    }
                }
            ],
            'status' => 'boolean'
        ], [
            'name.required' => 'The category name is required',
            'name.unique' => 'This category name is already taken in your business',
            'name.max' => 'The category name cannot exceed 255 characters',
            'ledger_id.required' => 'A supplier ledger must be selected',
            'status.boolean' => 'Status must be active or inactive'
        ]);

        // Generate a unique slug by appending the business_id
        $baseSlug = Str::slug($validated['name']);
        $uniqueSlug = $baseSlug . '-' . $currentAdmin->business_id;

        // Update the category with the new data
        $category->update([
            'name' => $validated['name'],
            'slug' => $uniqueSlug,
            'ledger_id' => $validated['ledger_id'],
            'status' => $validated['status']
        ]);

        return redirect()->route('admin.inventory.categories.index')
            ->with('success', 'Category updated successfully');
    }

    public function destroy(ProductCategory $category)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        if ($category->business_id !== $currentAdmin->business_id) {
            abort(403);
        }

        try {
            DB::beginTransaction();

            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Delete products first
            \App\Models\Product::where('category_id', $category->id)->delete();

            // Delete the category
            DB::table('product_categories')->where('id', $category->id)->delete();

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            DB::commit();

            return redirect()->route('admin.inventory.categories.index')
                ->with('success', 'Category and its associated data deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            // Make sure to re-enable foreign key checks even if there's an error
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            Log::error('Failed to delete category: ' . $e->getMessage());

            return redirect()->route('admin.inventory.categories.index')
                ->with('error', 'Failed to delete category: ' . $e->getMessage());
        }
    }




    /**
     * Display a list of categories from the common database for importing.
     *
     * @return \Illuminate\Http\Response
     */
    public function showCommonCategories()
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        // Get categories from common database using the CommonCategory model
        // This model is already configured to use the pgsql_common connection
        $commonCategories = CommonCategory::all();

        // Get existing business categories to check which ones are already imported
        // Check by common_category_id instead of slug to avoid conflicts
        $existingCommonCategoryIds = ProductCategory::where('business_id', $currentAdmin->business_id)
            ->whereNotNull('common_category_id')
            ->pluck('common_category_id')
            ->toArray();

        // Also get existing category slugs to prevent slug conflicts (this was missing)
        $existingCategorySlugs = ProductCategory::where('business_id', $currentAdmin->business_id)
            ->pluck('slug')
            ->toArray();

        // Get supplier ledgers for the import form
        $ledgers = Ledger::where('business_id', $currentAdmin->business_id)
            ->where('ledger_type', 'Sundry Creditors (Supplier)')
            ->get();

        return view('admin.inventory.categories.import', [
            'commonCategories' => $commonCategories,
            'existingCommonCategoryIds' => $existingCommonCategoryIds,
            'existingCategorySlugs' => $existingCategorySlugs, // Add this line
            'ledgers' => $ledgers
        ]);
    }



    /**
     * Import a category from the common database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function importCategory(Request $request)
    {
        $validated = $request->validate([
            'common_category_id' => 'required|integer',
            'ledger_id' => 'required|exists:ledgers,id'
        ]);

        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        // Get category from common database using the CommonCategory model
        $commonCategory = CommonCategory::find($validated['common_category_id']);

        if (!$commonCategory) {
            return redirect()->back()->with('error', 'Category not found in common database');
        }

        // Check if category already exists for this business using common_category_id
        $existingCategory = ProductCategory::where('business_id', $currentAdmin->business_id)
            ->where('common_category_id', $commonCategory->category_id)
            ->first();

        if ($existingCategory) {
            return redirect()->back()->with('error', 'This category has already been imported');
        }

        // Generate a unique slug by combining the base slug with business_id
        $baseSlug = Str::slug($commonCategory->category_name);
        $uniqueSlug = $baseSlug . '-' . $currentAdmin->business_id;

        // Create new category in business database
        // Add common_category_id to store the relationship
        $category = ProductCategory::create([
            'name' => $commonCategory->category_name,
            'slug' => $uniqueSlug,
            'status' => true, // Default to active
            'business_id' => $currentAdmin->business_id,
            'ledger_id' => $validated['ledger_id'],
            'common_category_id' => $commonCategory->category_id // Store the common category ID
        ]);

        return redirect()->route('admin.inventory.categories.index')
            ->with('success', 'Category imported successfully');
    }
}
