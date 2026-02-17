<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\SuperAdmin\AdminController;
use App\Http\Controllers\SuperAdmin\BusinessController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\DamageTransactionController;
use App\Http\Controllers\DepositSlipController;
use App\Http\Controllers\ProductBatchController;
use App\Http\Controllers\InvoicePrintController;
use App\Http\Controllers\SuperAdmin\CustomerLedgerController;
use App\Http\Controllers\SuperAdmin\DefaultLedgerController;
use App\Http\Controllers\SuperAdmin\CommonCategoryController;
use App\Http\Controllers\SuperAdmin\CommonUnitController;
use App\Http\Controllers\SuperAdmin\CommonProductController;
use App\Http\Controllers\Admin\BusinessShopfrontController;
use App\Http\Controllers\ShopfrontOrderController;
use App\Http\Controllers\Admin\AdminShopfrontOrderController;
use App\Http\Controllers\ShopfrontController;
use App\Http\Controllers\Admin\CustomerImportController;
use App\Http\Controllers\Admin\BusinessSubDistrictController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\Admin\SalaryHeadController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/about', function () {
    return view('about');
})->name('about');



// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Add this route before the auth middleware group

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::get('/blog', function () {
    return view('blog');
})->name('blog');

// Add these routes for shopfront AJAX functionality
Route::get('/shopfront/{id}/category-image/{categoryKey}', [ShopfrontController::class, 'getCategoryImage'])
    ->name('shopfront.category.image');
Route::get('/shopfront/{id}/category-products/{categoryKey}', [ShopfrontController::class, 'getCategoryProducts'])
    ->name('shopfront.category.products');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Shopfront public routes
Route::get('/shop/{id}', [ShopfrontController::class, 'show'])->name('shopfront.show');
Route::get('/shop/{id}/cart', [ShopfrontController::class, 'cart'])->name('shopfront.cart');
Route::post('/shop/{id}/order', [ShopfrontOrderController::class, 'store'])->name('shopfront.order.store');
Route::get('/shopfront/{id}/invoice/{orderNumber}', [ShopfrontOrderController::class, 'showInvoice'])->name('shopfront.invoice');
Route::post('/shopfront/{id}/refresh-data', [ShopfrontController::class, 'refreshData'])->name('shopfront.refresh-data');

Route::middleware(['auth'])->group(function () {

    // Subscription routes
    Route::get('/subscription/payment', [SubscriptionController::class, 'showPaymentPage'])->name('subscription.payment');
    Route::post('/subscription/initiate-payment', [SubscriptionController::class, 'initiatePayment'])->name('subscription.initiate-payment');
    Route::post('/subscription/mark-payment-done', [SubscriptionController::class, 'markPaymentDone'])->name('subscription.mark-payment-done');
    Route::post('/subscription/verify-payment', [SubscriptionController::class, 'verifyPayment'])->name('subscription.verify-payment');
    // Super Admin only routes
    Route::middleware(['role:super-admin'])->group(function () {
        // AJAX FOR LOCATION DATA
        Route::get('location-data/download-template', [\App\Http\Controllers\SuperAdmin\LocationDataController::class, 'downloadTemplate'])
            ->name('super-admin.location-data.download-template');
        Route::get('location-data/export-data', [\App\Http\Controllers\SuperAdmin\LocationDataController::class, 'export'])
            ->name('super-admin.location-data.export');
        Route::post('location-data/bulk-import', [\App\Http\Controllers\SuperAdmin\LocationDataController::class, 'bulkImport'])
            ->name('super-admin.location-data.bulk-import');
        Route::get('location-data/ajax/sub-districts', [\App\Http\Controllers\SuperAdmin\LocationDataController::class, 'getSubDistricts'])
            ->name('super-admin.location-data.get-sub-districts');
        Route::get('location-data/ajax/villages', [\App\Http\Controllers\SuperAdmin\LocationDataController::class, 'getVillages'])
            ->name('super-admin.location-data.get-villages');

        Route::get('/super-admin/dashboard', [DashboardController::class, 'index'])->name('super-admin.dashboard');

        Route::resource('roles', RoleController::class);
        Route::resource('permissions', PermissionController::class);
        // All the Business routes
        Route::get('/businesses', [BusinessController::class, 'index'])->name('super-admin.businesses.index');
        Route::get('/businesses/create', [BusinessController::class, 'create'])->name('super-admin.businesses.create');
        Route::post('/businesses', [BusinessController::class, 'store'])->name('super-admin.businesses.store');
        Route::get('/businesses/{business}/edit', [BusinessController::class, 'edit'])->name('super-admin.businesses.edit');
        Route::put('/businesses/{business}', [BusinessController::class, 'update'])->name('super-admin.businesses.update');
        Route::delete('/businesses/{business}', [BusinessController::class, 'destroy'])->name('super-admin.businesses.destroy');
        Route::get('/businesses/search', [BusinessController::class, 'search'])->name('super-admin.businesses.search');
        // All the Admin routes
        Route::get('/admins', [AdminController::class, 'index'])->name('super-admin.admins.index');
        Route::get('/admins/create', [AdminController::class, 'create'])->name('super-admin.admins.create');
        Route::post('/admins', [AdminController::class, 'store'])->name('super-admin.admins.store');
        Route::get('/admins/{admin}/edit', [AdminController::class, 'edit'])->name('super-admin.admins.edit');
        Route::put('/admins/{admin}', [AdminController::class, 'update'])->name('super-admin.admins.update');
        Route::delete('/admins/{admin}', [AdminController::class, 'destroy'])->name('super-admin.admins.destroy');


        // Common Database Management Routes
        // Common Categories
        Route::resource('common-categories', CommonCategoryController::class)->names([
            'index' => 'super-admin.common-categories.index',
            'create' => 'super-admin.common-categories.create',
            'store' => 'super-admin.common-categories.store',
            'show' => 'super-admin.common-categories.show',
            'edit' => 'super-admin.common-categories.edit',
            'update' => 'super-admin.common-categories.update',
            'destroy' => 'super-admin.common-categories.destroy',
        ]);

        // Common Units
        Route::resource('common-units', CommonUnitController::class)->names([
            'index' => 'super-admin.common-units.index',
            'create' => 'super-admin.common-units.create',
            'store' => 'super-admin.common-units.store',
            'show' => 'super-admin.common-units.show',
            'edit' => 'super-admin.common-units.edit',
            'update' => 'super-admin.common-units.update',
            'destroy' => 'super-admin.common-units.destroy',
        ]);

        Route::get('common-products/import', [CommonProductController::class, 'showImport'])
            ->name('super-admin.common-products.import');
        Route::post('common-products/process-import', [CommonProductController::class, 'processImport'])
            ->name('super-admin.common-products.process-import');
        Route::get('common-products/import-template', [CommonProductController::class, 'downloadImportTemplate'])
            ->name('super-admin.common-products.import-template');

        // Common Products
        Route::resource('common-products', CommonProductController::class)->names([
            'index' => 'super-admin.common-products.index',
            'create' => 'super-admin.common-products.create',
            'store' => 'super-admin.common-products.store',
            'show' => 'super-admin.common-products.show',
            'edit' => 'super-admin.common-products.edit',
            'update' => 'super-admin.common-products.update',
            'destroy' => 'super-admin.common-products.destroy',
        ]);

        Route::get('common-products/{commonProduct}/image', [CommonProductController::class, 'showImage'])
            ->name('super-admin.common-products.image');


        // Default Ledgers
        Route::resource('default-ledgers', DefaultLedgerController::class)->names([
            'index' => 'super-admin.default-ledgers.index',
            'create' => 'super-admin.default-ledgers.create',
            'store' => 'super-admin.default-ledgers.store',
            'show' => 'super-admin.default-ledgers.show',
            'edit' => 'super-admin.default-ledgers.edit',
            'update' => 'super-admin.default-ledgers.update',
            'destroy' => 'super-admin.default-ledgers.destroy',
        ]);

        // Location Data Management Routes
        Route::resource('location-data', \App\Http\Controllers\SuperAdmin\LocationDataController::class)->names([
            'index' => 'super-admin.location-data.index',
            'create' => 'super-admin.location-data.create',
            'store' => 'super-admin.location-data.store',
            'show' => 'super-admin.location-data.show',
            'edit' => 'super-admin.location-data.edit',
            'update' => 'super-admin.location-data.update',
            'destroy' => 'super-admin.location-data.destroy',
        ]);
    });
    // Admin routes
    Route::middleware(['role:admin'])->group(function () {

        // Salary Heads Management Routes
        Route::resource('salary-heads', SalaryHeadController::class)->names([
            'index' => 'admin.salary-heads.index',
            'create' => 'admin.salary-heads.create',
            'store' => 'admin.salary-heads.store',
            'show' => 'admin.salary-heads.show',
            'edit' => 'admin.salary-heads.edit',
            'update' => 'admin.salary-heads.update',
            'destroy' => 'admin.salary-heads.destroy',
        ]);

        // Additional Salary Head Action Routes
        Route::post('salary-heads/{salaryHead}/approve', [SalaryHeadController::class, 'approve'])
            ->name('admin.salary-heads.approve');
        Route::post('salary-heads/{salaryHead}/reject', [SalaryHeadController::class, 'reject'])
            ->name('admin.salary-heads.reject');
        Route::post('salary-heads/bulk-approve', [SalaryHeadController::class, 'bulkApprove'])
            ->name('admin.salary-heads.bulk-approve');
        Route::get('salary-heads/statistics', [SalaryHeadController::class, 'getStatistics'])
            ->name('admin.salary-heads.statistics');
        Route::get('salary-heads/pending-count', [SalaryHeadController::class, 'getPendingCount'])
            ->name('admin.salary-heads.pending-count');

        Route::get('/admin/inventory/category-sales-summary/{category}', [InventoryTransactionController::class, 'categorySalesSummary'])
            ->name('admin.inventory.category-sales-summary');
        // Business Sub-Districts Management
        Route::resource('sub-districts', BusinessSubDistrictController::class)->names([
            'index' => 'admin.business.sub-districts.index',
            'store' => 'admin.business.sub-districts.store',
            'update' => 'admin.business.sub-districts.update',
            'destroy' => 'admin.business.sub-districts.destroy',
        ]);

        // Shopfront Images Routes
        Route::prefix('admin/shopfront/images')->name('admin.shopfront.images.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ShopfrontImageController::class, 'index'])->name('index');
            Route::post('/upload-hero-banner', [App\Http\Controllers\Admin\ShopfrontImageController::class, 'uploadHeroBanner'])->name('upload-hero-banner');
            Route::post('/upload-category-image', [App\Http\Controllers\Admin\ShopfrontImageController::class, 'uploadCategoryImage'])->name('upload-category-image');
            Route::post('/delete', [App\Http\Controllers\Admin\ShopfrontImageController::class, 'deleteImage'])->name('delete');
        });

        // Add this route to your existing admin shopfront routes
        Route::post('/admin/shopfront/images/upload-general-category-image', [App\Http\Controllers\Admin\ShopfrontImageController::class, 'uploadGeneralCategoryImage'])->name('admin.shopfront.images.upload-general-category-image');

        // Add this route in the admin routes section
        Route::get('/admin/get-business-name', [BusinessShopfrontController::class, 'getBusinessName'])
            ->name('admin.get-business-name');

        Route::get('sub-districts/get-sub-districts', [BusinessSubDistrictController::class, 'getSubDistricts'])
            ->name('admin.business.sub-districts.get-sub-districts');

        Route::get('/admin/inventory/common-products', [InventoryTransactionController::class, 'getCommonProducts'])
            ->name('admin.inventory.common-products');

        // Dashboard analytics routes
        Route::get('/admin/dashboard/sales-breakdown', [AdminDashboardController::class, 'salesBreakdown'])
            ->name('admin.dashboard.sales-breakdown');
        Route::get('/admin/dashboard/purchase-breakdown', [AdminDashboardController::class, 'purchaseBreakdown'])
            ->name('admin.dashboard.purchase-breakdown');
        Route::get('/admin/dashboard/purchase-breakdown', [AdminDashboardController::class, 'purchaseBreakdown'])
            ->name('admin.dashboard.purchase-breakdown');
        Route::get('/admin/dashboard/sales-return-breakdown', [AdminDashboardController::class, 'salesReturnBreakdown'])
            ->name('admin.dashboard.sales-return-breakdown');
        Route::get('/admin/dashboard/damage-return-breakdown', [AdminDashboardController::class, 'damageReturnBreakdown'])
            ->name('admin.dashboard.damage-return-breakdown');
        Route::get('/admin/dashboard/collection-breakdown', [AdminDashboardController::class, 'collectionBreakdown'])
            ->name('admin.dashboard.collection-breakdown');
        Route::get('/admin/dashboard/stock-breakdown', [AdminDashboardController::class, 'stockBreakdown'])
            ->name('admin.dashboard.stock-breakdown');
        Route::get('/admin/dashboard/balance-trends', [AdminDashboardController::class, 'balanceTrends'])
            ->name('admin.dashboard.balance-trends');
        Route::get('/admin/dashboard-data', [AdminDashboardController::class, 'dashboardData'])
            ->name('admin.dashboard-data');



        // New enhanced routes
        Route::post('/batches/bulk-delete', [ProductBatchController::class, 'bulkDelete'])->name('admin.batches.bulk-delete');
        Route::post('/batches/refresh-cache', [ProductBatchController::class, 'refreshCache'])->name('admin.batches.refresh-cache');

        // Replace the existing shopfront orders routes with this corrected version
        Route::prefix('admin/shopfront')->name('admin.shopfront.')->group(function () {
            // Shopfront management
            Route::get('/', [BusinessShopfrontController::class, 'index'])->name('index');
            Route::post('/generate', [BusinessShopfrontController::class, 'generate'])->name('generate');
            Route::post('/toggle-status', [BusinessShopfrontController::class, 'toggleStatus'])->name('toggle-status');

            // Orders management
            Route::prefix('orders')->name('orders.')->group(function () {
                Route::get('/', [AdminShopfrontOrderController::class, 'index'])->name('index');
                Route::get('/category-report', [AdminShopfrontOrderController::class, 'categoryReport'])->name('category-report');
                Route::get('/{order}', [AdminShopfrontOrderController::class, 'show'])->name('show');
                Route::get('/{order}/print', [AdminShopfrontOrderController::class, 'print'])->name('print');

                // Fix: Use only POST method for status updates to avoid confusion
                Route::post('/{order}/status', [AdminShopfrontOrderController::class, 'updateStatus'])->name('status');
                Route::delete('/{order}', [AdminShopfrontOrderController::class, 'destroy'])->name('destroy');
            });
        });


        Route::get('/admin/accounting/transactions/{transaction}/print', [TransactionController::class, 'print'])->name('admin.accounting.transactions.print');
        // Batch management api 
        Route::resource('batches', ProductBatchController::class)->names([
            'index' => 'admin.batches.index',
            'create' => 'admin.batches.create',
            'store' => 'admin.batches.store',
            'edit' => 'admin.batches.edit',
            'update' => 'admin.batches.update',
            'destroy' => 'admin.batches.destroy'
        ]);

        Route::prefix('admin/inventory')->group(function () {
            Route::get('/sales-summary', [InventoryTransactionController::class, 'salesSummary'])->name('admin.inventory.sales-summary');
            Route::get('/sales-summary/{category}', [InventoryTransactionController::class, 'categorySalesSummary'])->name('admin.inventory.category-sales-summary');
            Route::get('damage-summary', [InventoryTransactionController::class, 'damageSummary'])->name('admin.inventory.damage-summary');
            Route::get('damage-summary/{category}', [InventoryTransactionController::class, 'categoryDamageSummary'])->name('admin.inventory.category-damage-summary');
            Route::get('sales-return-summary', [InventoryTransactionController::class, 'salesReturnSummary'])->name('admin.inventory.sales-return-summary');
            Route::get('sales-return-summary/{category}', [InventoryTransactionController::class, 'categorySalesReturnSummary'])->name('admin.inventory.category-sales-return-summary');
            Route::get('stock-summary', [InventoryTransactionController::class, 'stockSummary'])->name('admin.inventory.stock-summary');
            Route::get('stock-summary/{category}', [InventoryTransactionController::class, 'categoryStockSummary'])->name('admin.inventory.category-stock-summary');
        });

        // Staff Management Routes
        Route::resource('staff', StaffController::class)->names([
            'index' => 'admin.staff.index',
            'create' => 'admin.staff.create',
            'store' => 'admin.staff.store',
            'edit' => 'admin.staff.edit',
            'update' => 'admin.staff.update',
            'destroy' => 'admin.staff.destroy',
            //'show' => 'admin.staff.show'
        ]);

        Route::prefix('admin/inventory/products')->group(function () {
            Route::get('import', [ProductImportController::class, 'index'])->name('admin.inventory.products.import');
            Route::post('import', [ProductImportController::class, 'import']);
            Route::get('download-sample', [ProductImportController::class, 'downloadSample'])->name('admin.inventory.products.download-sample');
        });

        Route::prefix('admin/accounting')->group(function () {
            Route::resource('transactions', TransactionController::class)->names([
                'index' => 'admin.accounting.transactions.index',
                'create' => 'admin.accounting.transactions.create',
                'store' => 'admin.accounting.transactions.store',
                'show' => 'admin.accounting.transactions.show',
                'edit' => 'admin.accounting.transactions.edit',
                'update' => 'admin.accounting.transactions.update',
                'destroy' => 'admin.accounting.transactions.destroy'
            ]);

            // Ledger Routes
            Route::resource('ledgers', LedgerController::class)->names([
                'index' => 'admin.accounting.ledgers.index',
                'create' => 'admin.accounting.ledgers.create',
                'store' => 'admin.accounting.ledgers.store',
                'edit' => 'admin.accounting.ledgers.edit',
                'update' => 'admin.accounting.ledgers.update',
                'destroy' => 'admin.accounting.ledgers.destroy'
            ]);
        });

        Route::get('/admin/accounting/reports/profit-loss', [LedgerController::class, 'profitAndLossReport'])
            ->name('admin.accounting.reports.profit-loss');
        Route::get('/admin/accounting/reports/balance-sheet', [LedgerController::class, 'balanceSheet'])
            ->name('admin.accounting.reports.balance-sheet');


        Route::prefix('admin/inventory')->group(function () {
            Route::get('products/generate-barcode', [ProductController::class, 'generateBarcode'])
                ->name('admin.inventory.products.generate-barcode');
        });

        Route::get('common-products/import-progress', [CommonProductController::class, 'getImportProgress'])
            ->name('super-admin.common-products.import-progress');

        // Product Management Routes
        Route::prefix('admin/inventory')->group(function () {

            Route::get('categories/common', [CategoryController::class, 'showCommonCategories'])
                ->name('admin.categories.common');

            Route::post('categories/import', [CategoryController::class, 'importCategory'])
                ->name('admin.categories.import');
            // Categories Routes
            Route::resource('categories', CategoryController::class)->names([
                'index' => 'admin.inventory.categories.index',
                'create' => 'admin.categories.create',
                'store' => 'admin.categories.store',
                'edit' => 'admin.categories.edit',
                'update' => 'admin.categories.update',
                'destroy' => 'admin.inventory.categories.destroy'
            ]);

            Route::post('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])
                ->name('admin.categories.toggle-status');

            // Product Management Routes
            Route::resource('products', ProductController::class)->names([
                'index' => 'admin.inventory.products.index',
                'create' => 'admin.inventory.products.create',
                'store' => 'admin.inventory.products.store',
                'edit' => 'admin.inventory.products.edit',
                'update' => 'admin.inventory.products.update',
                'destroy' => 'admin.inventory.products.destroy',
                'show' => 'admin.inventory.products.show'
            ]);

            Route::post('products/import', [ProductController::class, 'import'])->name('admin.products.import');
            Route::get('products/export', [ProductController::class, 'export'])->name('admin.products.export');
            Route::get('products/search', [ProductController::class, 'search'])->name('admin.products.search');
            Route::get('products/template', [ProductController::class, 'downloadTemplate'])->name('admin.products.template');
        });

        // Customer Import Routes - with the exact names your code expects
        Route::get('/admin/customer-import', [CustomerImportController::class, 'index'])->name('admin.customer-import.index');
        Route::get('/admin/customer-import/get-sub-districts', [CustomerImportController::class, 'getSubDistricts'])->name('admin.customer-import.get-sub-districts');
        Route::get('/admin/customer-import/get-villages', [CustomerImportController::class, 'getVillages'])->name('admin.customer-import.get-villages');
        Route::post('/admin/customer-import/preview', [CustomerImportController::class, 'preview'])->name('admin.customer-import.preview');
        Route::post('/admin/customer-import/import', [CustomerImportController::class, 'import'])->name('admin.customer-import.import');

        // History and batch management routes
        Route::get('/admin/customer-import/history', [CustomerImportController::class, 'history'])->name('admin.customer-import.history');
        Route::get('/admin/customer-import/history/{batchId}', [CustomerImportController::class, 'showBatch'])->name('admin.customer-import.show-batch');
        Route::post('/admin/customer-import/retry', [CustomerImportController::class, 'retryImport'])->name('admin.customer-import.retry');
        Route::delete('/admin/customer-import/delete-batch/{batchId}', [CustomerImportController::class, 'deleteBatch'])->name('admin.customer-import.delete-batch');
        Route::get('/admin/customer-import/export-batch/{batchId}', [CustomerImportController::class, 'exportBatch'])->name('admin.customer-import.export-batch');

        // Conflict management routes
        Route::get('/admin/customer-import/conflicts', [CustomerImportController::class, 'conflicts'])->name('admin.customer-import.conflicts');
        Route::get('/admin/customer-import/conflicts/{conflictId}', [CustomerImportController::class, 'showConflict'])->name('admin.customer-import.show-conflict');
        Route::post('/admin/customer-import/resolve-conflict', [CustomerImportController::class, 'resolveConflict'])->name('admin.customer-import.resolve-conflict');
        Route::post('/admin/customer-import/resolve-all-conflicts', [CustomerImportController::class, 'resolveAllConflicts'])->name('admin.customer-import.resolve-all-conflicts');
    });

    Route::middleware(['auth', 'role:admin|staff|dsr'])->group(function () {


        Route::get('/admin/inventory/customer-type/{id}', [InventoryTransactionController::class, 'determineCustomerType'])
            ->name('inventory.customer-type');


        Route::get('/admin/inventory/determine-customer-type/{ledgerId}', [InventoryTransactionController::class, 'determineCustomerType'])
            ->name('inventory.determine-customer-type');


        // Add this route for handling customer selection
        Route::post('/admin/inventory/get-or-create-local-customer', [InventoryTransactionController::class, 'getOrCreateLocalCustomer'])
            ->name('admin.inventory.get-or-create-local-customer');


        Route::post('/admin/inventory/inventory_transactions/{id}/delete-return', [InventoryTransactionController::class, 'deleteReturn'])->name('admin.inventory.inventory_transactions.delete-return');

        Route::post('/admin/inventory/inventory_transactions/{inventoryTransaction}/delete-collection', [App\Http\Controllers\InventoryTransactionController::class, 'deleteCollection'])
            ->name('admin.inventory.inventory_transactions.delete-collection');



        Route::post('ledgers/{ledger}/recalculate', [LedgerController::class, 'recalculateBalance'])->name('admin.accounting.ledgers.recalculate');

        Route::resource('ledgers', LedgerController::class)->names([
            'show' => 'admin.accounting.ledgers.show',
        ]);

        // FOR PRINTING THE INVOICES 
        Route::prefix('admin/invoices')->name('admin.invoices.')->group(function () {
            Route::get('/', [InvoicePrintController::class, 'index'])->name('index');
            Route::get('/print/{id}', [InvoicePrintController::class, 'print'])->name('print');
            Route::post('/batch-print', [InvoicePrintController::class, 'batchPrint'])->name('batch-print');
        });

        Route::prefix('admin/accounting')->name('admin.accounting.')->group(function () {
            Route::get('deposit', [DepositSlipController::class, 'index'])->name('deposit.index');
            Route::get('deposit/create', [DepositSlipController::class, 'create'])->name('deposit.create');
            Route::post('deposit', [DepositSlipController::class, 'store'])->name('deposit.store');
            Route::get('deposit/{depositSlip}', [DepositSlipController::class, 'show'])->name('deposit.show');
            Route::delete('deposit/{depositSlip}', [DepositSlipController::class, 'destroy'])->name('deposit.destroy');
            Route::patch('deposit/{depositSlip}/status', [DepositSlipController::class, 'updateStatus'])->name('deposit.status');
        });

        Route::resource('damage_transactions', DamageTransactionController::class)->names([
            'index' => 'admin.inventory.damage_transactions.index',
            'create' => 'admin.inventory.damage_transactions.create',
            'store' => 'admin.inventory.damage_transactions.store',
        ]);

        Route::resource('damage', DamageTransactionController::class)->names([
            'index' => 'admin.damage.index',
            'create' => 'admin.damage.create',
            'show' => 'admin.damage.show',
            'edit' => 'admin.damage.edit',
            'update' => 'admin.damage.update',
            'destroy' => 'admin.damage.destroy'
        ]);

        // Additional routes for damage transaction batch handling
        Route::get('/damage/{damage}/products', [DamageTransactionController::class, 'getProducts'])
            ->name('admin.damage.get-products');
        Route::get('/damage/batches/{productId}', [DamageTransactionController::class, 'getBatches'])
            ->name('admin.damage.get-batches');
        Route::patch('/damage/{damage}/toggle-status', [DamageTransactionController::class, 'toggleStatus'])
            ->name('admin.damage.toggle-status');

        Route::prefix('admin/inventory')->group(function () {

            Route::get(
                'check-transactions/{ledgerId}',
                [InventoryTransactionController::class, 'checkExistingTransactions']
            )->name('admin.inventory.check-transactions');

            Route::post(
                'append-transaction',
                [InventoryTransactionController::class, 'appendTransaction']
            )->name('admin.inventory.append-transaction');

            Route::resource('inventory_transactions', InventoryTransactionController::class)->names([
                'index' => 'admin.inventory.inventory_transactions.index',
                'create' => 'admin.inventory.inventory_transactions.create',
                'store' => 'admin.inventory.inventory_transactions.store',
                'show' => 'admin.inventory.inventory_transactions.show',
                'edit' => 'admin.inventory.inventory_transactions.edit',
                'update' => 'admin.inventory.inventory_transactions.update',
                'destroy' => 'admin.inventory.inventory_transactions.destroy'

            ]);

            // New routes for product returns
            Route::get(
                'inventory_transactions/{inventoryTransaction}/products',
                [InventoryTransactionController::class, 'getProducts']
            )
                ->name('admin.inventory.inventory_transactions.products');

            Route::post(
                'inventory_transactions/{inventoryTransaction}/return',
                [InventoryTransactionController::class, 'returnProducts']
            )
                ->name('admin.inventory.inventory_transactions.return');

            // New collection route
            Route::match(['get', 'post'], 'inventory_transactions/{inventoryTransaction}/collect', [InventoryTransactionController::class, 'collectPayment'])
                ->name('admin.inventory.inventory_transactions.collect');

            Route::get('products/{product}/image', [ProductController::class, 'showImage'])
                ->name('admin.inventory.products.image');
        });
    });

    // CMS ROUTES STARTS HERE
    Route::middleware(['auth', 'role:admin|staff|super-admin|dsr'])->group(function () {

        Route::get('/admin/accounting/diagnose-balance-sheet', [LedgerController::class, 'diagnoseBalanceSheet'])->name('admin.accounting.diagnose-balance-sheet');
        Route::get('/admin/accounting/debug-balance-sheet', [LedgerController::class, 'debugBalanceSheet'])->name('admin.accounting.debug-balance-sheet');

        // Add these routes for business sub-districts management
        Route::prefix('admin/business/sub-districts')->name('admin.business.sub-districts.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\BusinessSubDistrictController::class, 'index'])->name('index');
            Route::get('/get-sub-districts', [App\Http\Controllers\Admin\BusinessSubDistrictController::class, 'getSubDistricts'])->name('get-sub-districts');
            Route::post('/', [App\Http\Controllers\Admin\BusinessSubDistrictController::class, 'store'])->name('store');
            Route::put('/{subDistrict}', [App\Http\Controllers\Admin\BusinessSubDistrictController::class, 'update'])->name('update');
            Route::delete('/{subDistrict}', [App\Http\Controllers\Admin\BusinessSubDistrictController::class, 'destroy'])->name('destroy');
        });

        Route::post('/admin/inventory/create-local-customer', [InventoryTransactionController::class, 'createLocalCustomer'])->name('admin.inventory.create_local_customer');


        Route::get('/admin/dashboard', [AdminDashboardController::class, 'dashboard'])->name('admin.dashboard');
        // Test route for duplicate detection
        Route::get('test/duplicate-detection', function () {
            return view('super-admin.customer-ledgers.duplicate-detection');
        })->name('test.duplicate-detection');

        Route::post('customer-ledgers/{customerLedger}/generate-qr', [CustomerLedgerController::class, 'generateQrCode'])
            ->name('super-admin.customer-ledgers.generate-qr');
        // Customer Ledgers - Add these BEFORE the resource routes
        Route::get('customer-ledgers/ajax/sub-districts', [CustomerLedgerController::class, 'getSubDistricts'])
            ->name('super-admin.customer-ledgers.get-sub-districts');
        Route::get('customer-ledgers/ajax/villages', [CustomerLedgerController::class, 'getVillages'])
            ->name('super-admin.customer-ledgers.get-villages');
        Route::post('customer-ledgers/ajax/check-duplicates', [CustomerLedgerController::class, 'checkDuplicates'])
            ->name('super-admin.customer-ledgers.check-duplicates');
        Route::post('customer-ledgers/merge', [CustomerLedgerController::class, 'mergeDuplicates'])
            ->name('super-admin.customer-ledgers.merge');

        // ADD THESE NEW ROUTES
        Route::get('customer-ledgers/data-quality-report', [CustomerLedgerController::class, 'dataQualityReport'])
            ->name('super-admin.customer-ledgers.data-quality-report');
        Route::get('customer-ledgers/merge-history', [CustomerLedgerController::class, 'mergeHistory'])
            ->name('super-admin.customer-ledgers.merge-history');
        Route::post('customer-ledgers/bulk-update-quality', [CustomerLedgerController::class, 'bulkUpdateQuality'])
            ->name('super-admin.customer-ledgers.bulk-update-quality');
        Route::get('customer-ledgers/export', [CustomerLedgerController::class, 'export'])
            ->name('super-admin.customer-ledgers.export');
        Route::post('customer-ledgers/import', [CustomerLedgerController::class, 'import'])
            ->name('super-admin.customer-ledgers.import');
        Route::get('customer-ledgers/import-template', [CustomerLedgerController::class, 'downloadImportTemplate'])
            ->name('super-admin.customer-ledgers.import-template');

        // Customer Ledgers
        Route::resource('customer-ledgers', CustomerLedgerController::class)->names([
            'index' => 'super-admin.customer-ledgers.index',
            'store' => 'super-admin.customer-ledgers.store',
            'create' => 'super-admin.customer-ledgers.create',
            'show' => 'super-admin.customer-ledgers.show',
            'edit' => 'super-admin.customer-ledgers.edit',
            'update' => 'super-admin.customer-ledgers.update',
            'destroy' => 'super-admin.customer-ledgers.destroy',
        ]);
    });
});

require __DIR__ . '/auth.php';
