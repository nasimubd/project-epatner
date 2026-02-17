<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessAdmin;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Ledger;
use App\Models\ReturnedProduct;
use App\Models\DamageTransactionLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\ProductCategory;
use App\Models\InventoryTransactionLine;
use App\Models\Transaction;
use App\Models\TransactionLine;

class AdminDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $businessId = null;
        $staff = Staff::where('user_id', Auth::id())->with('productCategories')->first();
        $assignedCategories = collect();

        if (Auth::user()->roles->contains('name', 'staff')) {
            $staff = Staff::where('user_id', Auth::id())->with('productCategories')->first();
            $businessId = $staff->business_id;
            $assignedCategories = $staff->productCategories;
        } elseif (Auth::user()->roles->contains('name', 'admin')) {
            $businessId = BusinessAdmin::where('user_id', Auth::id())->first()->business_id;
        }

        // Get date range
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfDay()->format('Y-m-d'));

        // Dashboard for staff 
        // Get transactions for assigned categories
        $staffTransactions = InventoryTransaction::where('business_id', $businessId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('lines.product', function ($query) use ($assignedCategories) {
                $query->whereIn('category_id', $assignedCategories->pluck('id'));
            })
            ->get();

        // Calculate summaries for staff
        $salesSummary = $staffTransactions->where('entry_type', 'sale')->sum('grand_total');
        $damageSummary = $staffTransactions->where('entry_type', 'damage')->sum('grand_total');
        $returnSummary = $staffTransactions->where('entry_type', 'sales_return')->sum('grand_total');

        // Get category-wise stock report with total amount
        $stockReport = Product::whereIn('category_id', $assignedCategories->pluck('id'))
            ->with('category')
            ->select(
                'category_id',
                DB::raw('SUM(current_stock * dealer_price) as total_amount')
            )
            ->groupBy('category_id')
            ->get();

        //----------------Staff dashboard end here --------------------

        // Get products with detailed information for accurate calculations - SAME AS STOCK SUMMARY
        $products = Product::where('business_id', $businessId)
            ->with([
                'category',
                'inventoryTransactionLines' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->with('inventoryTransaction', 'batch');
                },
                'returnedProducts' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('return_date', [$startDate, $endDate])
                        ->with('batch');
                },
                'damageTransactionLines' => function ($query) use ($startDate, $endDate) {
                    $query->whereHas('damageTransaction', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('transaction_date', [$startDate, $endDate]);
                    });
                }
            ])
            ->get();

        // Calculate Opening Stock Value - SAME AS STOCK SUMMARY
        $openingStock = $products->sum(function ($product) {
            return $product->opening_stock * $product->trade_price;
        });

        // Count low stock products - products where current_stock <= quantity_alert
        $lowStockProducts = $products->filter(function ($product) {
            return $product->current_stock <= $product->quantity_alert;
        })->count();

        $totalProducts = $products->count();

        // Initialize variables for total calculations - USING STOCK SUMMARY LOGIC
        $totalSalesValue = 0;
        $totalCOGS = 0;
        $closingStock = 0;
        $totalPurchases = 0;
        $totalGrossProfit = 0;

        // Calculate product-level metrics following stockSummary approach EXACTLY
        foreach ($products as $product) {
            // Calculate beginning inventory - SAME AS STOCK SUMMARY
            $beginningInventory = $product->opening_stock * $product->trade_price;

            // Calculate purchases - SAME AS STOCK SUMMARY
            $purchaseLines = $product->inventoryTransactionLines
                ->where('inventoryTransaction.entry_type', 'purchase');
            $purchases = $purchaseLines->sum('line_total');

            // Add purchase value to total purchases
            $totalPurchases += $purchases;

            // Calculate sales and revenue using the EXACT same method as stockSummary
            $salesLines = $product->inventoryTransactionLines
                ->where('inventoryTransaction.entry_type', 'sale');

            // Sales value using trade price from batch - EXACT SAME AS STOCK SUMMARY
            $salesValue = $salesLines->sum(function ($line) {
                return $line->quantity * ($line->batch ? $line->batch->trade_price : 0);
            });

            // Calculate COGS - EXACT SAME AS STOCK SUMMARY
            $cogs = $salesLines->sum(function ($line) {
                return $line->quantity * $line->dealer_price;
            });

            // Calculate initial gross profit - EXACT SAME AS STOCK SUMMARY
            $initialGrossProfit = $salesValue - $cogs;

            // Get return data for this product - SAME AS STOCK SUMMARY
            $returnedProducts = $product->returnedProducts;
            $returnValue = $returnedProducts->sum('total_amount');

            // Calculate profit adjustment for returns - SAME AS STOCK SUMMARY
            $returnProfitAdjustment = 0;
            foreach ($returnedProducts as $returnedProduct) {
                $batch = $returnedProduct->batch;
                if ($batch) {
                    $tradePriceForReturn = $batch->trade_price;
                    $dealerPriceForReturn = $batch->dealer_price;
                    $priceDifference = $tradePriceForReturn - $dealerPriceForReturn;
                    $returnProfitAdjustment += $priceDifference * $returnedProduct->quantity;
                } else {
                    $tradePriceForReturn = $returnedProduct->unit_price;
                    $dealerPriceForReturn = $product->dealer_price ?? ($tradePriceForReturn * 0.8);
                    $priceDifference = $tradePriceForReturn - $dealerPriceForReturn;
                    $returnProfitAdjustment += $priceDifference * $returnedProduct->quantity;
                }
            }

            // Get damage data for this product - SAME AS STOCK SUMMARY
            $damageLines = $product->damageTransactionLines ?? collect();
            $damageValue = 0;
            foreach ($damageLines as $damageLine) {
                $productItem = Product::find($damageLine->product_id);
                if ($productItem) {
                    $dealerPrice = $productItem->dealer_price;
                    $batch = $productItem->batches()
                        ->whereNotNull('dealer_price')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($batch) {
                        $dealerPrice = $batch->dealer_price;
                    }
                    if ($dealerPrice) {
                        $damageValue += $damageLine->quantity * $dealerPrice;
                    } else {
                        $damageValue += $damageLine->total_value;
                    }
                } else {
                    $damageValue += $damageLine->total_value;
                }
            }

            // Adjust gross profit - SAME AS STOCK SUMMARY
            $adjustedGrossProfit = $initialGrossProfit;

            // Calculate closing value using the formula - SAME AS STOCK SUMMARY
            $productClosingValue = $beginningInventory + $purchases - $cogs;

            // Add to totals
            $totalSalesValue += $salesValue;
            $totalCOGS += $cogs;
            $closingStock += $productClosingValue;
            $totalGrossProfit += $adjustedGrossProfit;
        }

        // Use the calculated values from stock summary logic
        $totalSales = $totalSalesValue;
        $totalPurchase = $totalPurchases;

        // Get all transactions for the date range
        $transactions = InventoryTransaction::where('business_id', $businessId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalMemo = $transactions->count();

        // Calculate Sales Return using ReturnedProduct table - same as in salesReturnSummary method
        $salesReturn = ReturnedProduct::where('business_id', $businessId)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('total_amount');

        // Calculate Damage Return using DamageTransactionLine table - same as in damageSummary method
        $damageReturn = DamageTransactionLine::whereHas('damageTransaction', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
        })
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('total_value');

        // Get all ledgers
        $ledgers = Ledger::where('business_id', $businessId)->get();

        // Calculate ledger balances based on their types
        $cashInHand = $ledgers->where('ledger_type', 'Cash-in-Hand')->sum('current_balance');
        $bankBalance = $ledgers->where('ledger_type', 'Bank Accounts')->sum('current_balance');
        $stockInHand = $closingStock; // Use the calculated closing stock value
        $customerDue = $ledgers->where('ledger_type', 'Sundry Debtors (Customer)')->sum('current_balance');
        $supplierDue = $ledgers->where('ledger_type', 'Sundry Creditors (Supplier)')->sum('current_balance');

        // Calculate expense and income totals
        $totalExpense = $ledgers->where('ledger_type', 'Expenses')->sum('current_balance');
        $totalIncome = $ledgers->where('ledger_type', 'Incomes')->sum('current_balance');

        // FIXED: Use gross profit from stock summary calculation instead of P&L formula
        $netProfitLoss = $totalGrossProfit;

        // FIXED: Calculate DSR Collection - sum of balances from ledgers assigned to DSR staff
        $totalCollection = $this->calculateDSRCollection($businessId);

        // Calculate other ledger balances for the balance sheet
        $securityAndAdvance = $ledgers->where('ledger_type', 'Loans & Advances (Asset)')->sum('current_balance');
        $totalLoans = $ledgers->where('ledger_type', 'Loans A/c')->sum('current_balance');
        $totalBankOD = $ledgers->where('ledger_type', 'Bank OD A/c')->sum('current_balance');
        $totalCapital = $ledgers->where('ledger_type', 'Capital Accounts')->sum('current_balance');
        $totalInvestment = $ledgers->where('ledger_type', 'Investments')->sum('current_balance');
        $totalFixedAssets = $ledgers->where('ledger_type', 'Fixed Assets')->sum('current_balance');
        $dutiesAndTaxes = $ledgers->where('ledger_type', 'Duties & Taxes')->sum('current_balance');

        // Calculate total liability - sum of supplier dues, loans, and bank overdrafts
        $totalLiability = $supplierDue + $totalLoans + $totalBankOD;

        // Get staff counts
        $staffCount = Staff::where('business_id', $businessId)->count();
        $partnersCount = BusinessAdmin::where('business_id', $businessId)->count();

        // Calculate P&L components for balance sheet (keep original for balance sheet)
        $debitSide = $openingStock + $totalPurchase + $totalExpense;
        $creditSide = $closingStock + $totalSales + $totalIncome;

        return view('admin.dashboard', compact(
            'totalSales',
            'totalPurchase',
            'salesReturn',
            'damageReturn',
            'cashInHand',
            'bankBalance',
            'stockInHand',
            'customerDue',
            'supplierDue',
            'totalIncome',
            'totalExpense',
            'netProfitLoss',
            'lowStockProducts',
            'staffCount',
            'partnersCount',
            'securityAndAdvance',
            'totalLoans',
            'totalBankOD',
            'totalCapital',
            'totalInvestment',
            'totalFixedAssets',
            'totalProducts',
            'totalMemo',
            'totalLiability',
            'dutiesAndTaxes',
            'openingStock',
            'closingStock',
            'debitSide',
            'creditSide',
            'salesSummary',
            'damageSummary',
            'returnSummary',
            'stockReport',
            'totalCollection'
        ));
    }

    /**
     * Calculate DSR Collection - sum of balances from ledgers assigned to DSR staff
     */
    private function calculateDSRCollection($businessId)
    {
        $dsrStaff = Staff::where('business_id', $businessId)
            ->whereHas('user.roles', function ($query) {
                $query->where('name', 'dsr');
            })
            ->with('ledgers')
            ->get();

        return $dsrStaff->sum(function ($staff) {
            return $staff->ledgers->sum('current_balance');
        });
    }

    public function index()
    {
        return view('admin.index');
    }

    /**
     * Get category-wise sales breakdown for dashboard
     */
    public function salesBreakdown(Request $request)
    {
        try {
            $businessId = null;
            if (Auth::user()->roles->contains('name', 'staff')) {
                $staff = Staff::where('user_id', Auth::id())->first();
                $businessId = $staff->business_id;
            } elseif (Auth::user()->roles->contains('name', 'admin')) {
                $businessId = BusinessAdmin::where('user_id', Auth::id())->first()->business_id;
            }

            // Get date range from request or use defaults
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->endOfDay()->format('Y-m-d'));

            // Get all categories for this business
            $categories = ProductCategory::where('business_id', $businessId)
                ->with(['products.inventoryTransactionLines' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->whereHas('inventoryTransaction', function ($q) {
                            $q->where('entry_type', 'sale');
                        })
                        ->with('inventoryTransaction', 'batch');
                }])
                ->get()
                ->map(function ($category) {
                    $totalSales = $category->products->sum(function ($product) {
                        return $product->inventoryTransactionLines->sum('line_total');
                    });

                    $totalQuantity = $category->products->sum(function ($product) {
                        return $product->inventoryTransactionLines->sum('quantity');
                    });

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'total_sales' => $totalSales,
                        'total_quantity' => $totalQuantity
                    ];
                })
                ->filter(function ($category) {
                    // Only include categories with sales
                    return $category['total_sales'] > 0;
                })
                ->values();

            // Generate trend data for sales
            $trendData = $this->generateSalesTrendData($businessId, $startDate, $endDate);

            return response()->json([
                'categories' => $categories,
                'trend_data' => $trendData,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error fetching sales data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category-wise purchase breakdown for dashboard
     */
    public function purchaseBreakdown(Request $request)
    {
        try {
            $businessId = null;
            if (Auth::user()->roles->contains('name', 'staff')) {
                $staff = Staff::where('user_id', Auth::id())->first();
                $businessId = $staff->business_id;
            } elseif (Auth::user()->roles->contains('name', 'admin')) {
                $businessId = BusinessAdmin::where('user_id', Auth::id())->first()->business_id;
            }

            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->endOfDay()->format('Y-m-d'));

            $categories = ProductCategory::where('business_id', $businessId)
                ->with(['products.inventoryTransactionLines' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->whereHas('inventoryTransaction', function ($q) {
                            $q->where('entry_type', 'purchase');
                        })
                        ->with('inventoryTransaction');
                }])
                ->get()
                ->map(function ($category) {
                    $totalPurchases = $category->products->sum(function ($product) {
                        return $product->inventoryTransactionLines->sum('line_total');
                    });

                    $totalQuantity = $category->products->sum(function ($product) {
                        return $product->inventoryTransactionLines->sum('quantity');
                    });

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'total_purchases' => $totalPurchases,
                        'total_quantity' => $totalQuantity
                    ];
                })
                ->filter(function ($category) {
                    return $category['total_purchases'] > 0;
                })
                ->values();

            // Generate trend data for purchases
            $trendData = $this->generatePurchaseTrendData($businessId, $startDate, $endDate);

            return response()->json([
                'categories' => $categories,
                'trend_data' => $trendData,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error fetching purchase data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category-wise sales return breakdown for dashboard
     */
    public function salesReturnBreakdown(Request $request)
    {
        try {
            $businessId = null;
            if (Auth::user()->roles->contains('name', 'staff')) {
                $staff = Staff::where('user_id', Auth::id())->first();
                $businessId = $staff->business_id;
            } elseif (Auth::user()->roles->contains('name', 'admin')) {
                $businessId = BusinessAdmin::where('user_id', Auth::id())->first()->business_id;
            }

            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->endOfDay()->format('Y-m-d'));

            $categories = ProductCategory::where('business_id', $businessId)
                ->with(['products' => function ($query) use ($startDate, $endDate) {
                    $query->with(['returnedProducts' => function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                    }]);
                }])
                ->get()
                ->map(function ($category) {
                    $totalReturns = $category->products->sum(function ($product) {
                        return $product->returnedProducts->sum('total_amount');
                    });

                    $totalQuantity = $category->products->sum(function ($product) {
                        return $product->returnedProducts->sum('quantity');
                    });

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'total_returns' => $totalReturns,
                        'total_quantity' => $totalQuantity
                    ];
                })
                ->filter(function ($category) {
                    return $category['total_returns'] > 0;
                })
                ->values();

            // Generate trend data for sales returns
            $trendData = $this->generateSalesReturnTrendData($businessId, $startDate, $endDate);

            return response()->json([
                'categories' => $categories,
                'trend_data' => $trendData,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error fetching sales return data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category-wise damage return breakdown for dashboard
     */
    public function damageReturnBreakdown(Request $request)
    {
        try {
            $businessId = null;
            if (Auth::user()->roles->contains('name', 'staff')) {
                $staff = Staff::where('user_id', Auth::id())->first();
                $businessId = $staff->business_id;
            } elseif (Auth::user()->roles->contains('name', 'admin')) {
                $businessId = BusinessAdmin::where('user_id', Auth::id())->first()->business_id;
            }

            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->endOfDay()->format('Y-m-d'));

            $categories = ProductCategory::where('business_id', $businessId)
                ->with(['products' => function ($query) use ($startDate, $endDate) {
                    $query->with(['damageTransactionLines' => function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                            ->whereHas('damageTransaction', function ($dt) {
                                $dt->where('status', 'approved');
                            });
                    }]);
                }])
                ->get()
                ->map(function ($category) {
                    $totalDamage = $category->products->sum(function ($product) {
                        return $product->damageTransactionLines->sum('total_value');
                    });

                    $totalQuantity = $category->products->sum(function ($product) {
                        return $product->damageTransactionLines->sum('quantity');
                    });

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'total_damage' => $totalDamage,
                        'total_quantity' => $totalQuantity
                    ];
                })
                ->filter(function ($category) {
                    return $category['total_damage'] > 0;
                })
                ->values();

            // Generate trend data for damage returns
            $trendData = $this->generateDamageTrendData($businessId, $startDate, $endDate);

            return response()->json([
                'categories' => $categories,
                'trend_data' => $trendData,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error fetching damage data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * UPDATED: Get DSR collection breakdown for dashboard
     */
    public function collectionBreakdown(Request $request)
    {
        try {
            $businessId = null;
            if (Auth::user()->roles->contains('name', 'staff')) {
                $staff = Staff::where('user_id', Auth::id())->first();
                $businessId = $staff->business_id;
            } elseif (Auth::user()->roles->contains('name', 'admin')) {
                $businessId = BusinessAdmin::where('user_id', Auth::id())->first()->business_id;
            }

            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->endOfDay()->format('Y-m-d'));

            // Get DSR staff with their assigned ledgers
            $dsrStaff = Staff::where('business_id', $businessId)
                ->whereHas('user.roles', function ($query) {
                    $query->where('name', 'dsr');
                })
                ->with(['user', 'ledgers'])
                ->get();

            $categories = $dsrStaff->map(function ($staff) {
                $totalBalance = $staff->ledgers->sum('current_balance');

                return [
                    'id' => $staff->id,
                    'name' => $staff->user->name . ' (DSR)',
                    'total_collection' => $totalBalance,
                    'ledger_count' => $staff->ledgers->count(),
                    'ledgers' => $staff->ledgers->map(function ($ledger) {
                        return [
                            'name' => $ledger->name,
                            'balance' => $ledger->current_balance,
                            'type' => $ledger->ledger_type
                        ];
                    })
                ];
            })->filter(function ($item) {
                return $item['total_collection'] != 0; // Show both positive and negative balances
            })->values();

            // Generate trend data for DSR collections
            $trendData = $this->generateDSRCollectionTrendData($businessId, $startDate, $endDate);

            return response()->json([
                'categories' => $categories,
                'trend_data' => $trendData,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error fetching DSR collection data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category-wise stock breakdown for dashboard
     */
    public function stockBreakdown(Request $request)
    {
        try {
            $businessId = null;
            if (Auth::user()->roles->contains('name', 'staff')) {
                $staff = Staff::where('user_id', Auth::id())->first();
                $businessId = $staff->business_id;
            } elseif (Auth::user()->roles->contains('name', 'admin')) {
                $businessId = BusinessAdmin::where('user_id', Auth::id())->first()->business_id;
            }

            $categories = ProductCategory::where('business_id', $businessId)
                ->with(['products' => function ($query) {
                    $query->where('current_stock', '>', 0);
                }])
                ->get()
                ->map(function ($category) {
                    $totalAmount = $category->products->sum(function ($product) {
                        return $product->current_stock * $product->dealer_price;
                    });

                    $totalQuantity = $category->products->sum('current_stock');

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'total_amount' => $totalAmount,
                        'total_quantity' => $totalQuantity
                    ];
                })
                ->filter(function ($category) {
                    return $category['total_amount'] > 0;
                })
                ->values();

            // For stock, we don't need trend data as it's current stock
            // But we can show stock movement trend if needed
            $trendData = [];

            return response()->json([
                'categories' => $categories,
                'trend_data' => $trendData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error fetching stock data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate sales trend data
     */
    private function generateSalesTrendData($businessId, $startDate, $endDate)
    {
        $trendData = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');

            $dailySales = InventoryTransaction::where('business_id', $businessId)
                ->where('entry_type', 'sale')
                ->whereDate('created_at', $dateStr)
                ->sum('grand_total');

            $trendData[] = [
                'date' => $dateStr,
                'value' => $dailySales
            ];

            $current->addDay();
        }

        return $trendData;
    }

    /**
     * Generate purchase trend data
     */
    private function generatePurchaseTrendData($businessId, $startDate, $endDate)
    {
        $trendData = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');

            $dailyPurchases = InventoryTransaction::where('business_id', $businessId)
                ->where('entry_type', 'purchase')
                ->whereDate('created_at', $dateStr)
                ->sum('grand_total');

            $trendData[] = [
                'date' => $dateStr,
                'value' => $dailyPurchases
            ];

            $current->addDay();
        }

        return $trendData;
    }

    /**
     * Generate sales return trend data
     */
    private function generateSalesReturnTrendData($businessId, $startDate, $endDate)
    {
        $trendData = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');

            $dailyReturns = ReturnedProduct::where('business_id', $businessId)
                ->whereDate('created_at', $dateStr)
                ->sum('total_amount');

            $trendData[] = [
                'date' => $dateStr,
                'value' => $dailyReturns
            ];

            $current->addDay();
        }

        return $trendData;
    }

    /**
     * Generate damage trend data
     */
    private function generateDamageTrendData($businessId, $startDate, $endDate)
    {
        $trendData = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');

            $dailyDamage = DamageTransactionLine::whereHas('damageTransaction', function ($query) use ($businessId) {
                $query->where('business_id', $businessId)->where('status', 'approved');
            })
                ->whereDate('created_at', $dateStr)
                ->sum('total_value');

            $trendData[] = [
                'date' => $dateStr,
                'value' => $dailyDamage
            ];

            $current->addDay();
        }

        return $trendData;
    }

    /**
     * NEW: Generate DSR collection trend data
     */
    private function generateDSRCollectionTrendData($businessId, $startDate, $endDate)
    {
        $trendData = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Get DSR staff IDs
        $dsrStaffIds = Staff::where('business_id', $businessId)
            ->whereHas('user.roles', function ($query) {
                $query->where('name', 'dsr');
            })
            ->pluck('id');

        // Get ledger IDs assigned to DSR staff
        $dsrLedgerIds = DB::table('staff_ledgers')
            ->whereIn('staff_id', $dsrStaffIds)
            ->pluck('ledger_id');

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $endOfDay = $dateStr . ' 23:59:59';

            // Calculate cumulative balance for DSR ledgers up to this date
            $dailyBalance = 0;
            foreach ($dsrLedgerIds as $ledgerId) {
                $ledgerBalance = TransactionLine::where('ledger_id', $ledgerId)
                    ->whereHas('transaction', function ($query) use ($businessId, $endOfDay) {
                        $query->where('business_id', $businessId)
                            ->where('created_at', '<=', $endOfDay);
                    })
                    ->get();

                // Calculate balance based on ledger type
                $ledger = Ledger::find($ledgerId);
                if ($ledger) {
                    $balance = 0;
                    foreach ($ledgerBalance as $line) {
                        if (in_array($ledger->ledger_type, ['Sundry Debtors (Customer)', 'Fixed Assets', 'Cash-in-Hand', 'Bank Accounts'])) {
                            $balance += $line->debit_amount - $line->credit_amount;
                        } else {
                            $balance += $line->credit_amount - $line->debit_amount;
                        }
                    }
                    $dailyBalance += $balance;
                }
            }

            $trendData[] = [
                'date' => $dateStr,
                'value' => $dailyBalance
            ];

            $current->addDay();
        }

        return $trendData;
    }

    /**
     * Get balance trends for dashboard
     */
    public function balanceTrends(Request $request)
    {
        try {
            $businessId = null;
            if (Auth::user()->roles->contains('name', 'staff')) {
                $staff = Staff::where('user_id', Auth::id())->first();
                $businessId = $staff->business_id;
            } elseif (Auth::user()->roles->contains('name', 'admin')) {
                $businessId = BusinessAdmin::where('user_id', Auth::id())->first()->business_id;
            }

            $type = $request->get('type');
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            // Generate date range
            $dates = [];
            $current = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            while ($current <= $end) {
                $dates[] = $current->format('Y-m-d');
                $current->addDay();
            }

            $trendData = [];

            foreach ($dates as $date) {
                $balance = $this->calculateBalanceForDate($businessId, $type, $date);
                $trendData[] = [
                    'date' => $date,
                    'value' => $balance
                ];
            }

            return response()->json([
                'trend_data' => $trendData,
                'type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error fetching balance trends: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate balance for a specific date and type
     */
    private function calculateBalanceForDate($businessId, $type, $date)
    {
        $endOfDay = $date . ' 23:59:59';

        switch ($type) {
            case 'cash':
                // For cash, we need to calculate cumulative transactions up to this date
                $cashTransactions = TransactionLine::whereHas('transaction', function ($query) use ($businessId, $endOfDay) {
                    $query->where('business_id', $businessId)
                        ->where('created_at', '<=', $endOfDay);
                })
                    ->whereHas('ledger', function ($query) {
                        $query->where('ledger_type', 'Cash-in-Hand');
                    })
                    ->get();

                return $cashTransactions->sum('debit_amount') - $cashTransactions->sum('credit_amount');

            case 'bank':
                $bankTransactions = TransactionLine::whereHas('transaction', function ($query) use ($businessId, $endOfDay) {
                    $query->where('business_id', $businessId)
                        ->where('created_at', '<=', $endOfDay);
                })
                    ->whereHas('ledger', function ($query) {
                        $query->where('ledger_type', 'Bank Accounts');
                    })
                    ->get();

                return $bankTransactions->sum('debit_amount') - $bankTransactions->sum('credit_amount');

            case 'customer_due':
                $customerTransactions = TransactionLine::whereHas('transaction', function ($query) use ($businessId, $endOfDay) {
                    $query->where('business_id', $businessId)
                        ->where('created_at', '<=', $endOfDay);
                })
                    ->whereHas('ledger', function ($query) {
                        $query->where('ledger_type', 'Sundry Debtors (Customer)');
                    })
                    ->get();

                return $customerTransactions->sum('debit_amount') - $customerTransactions->sum('credit_amount');

            case 'supplier_due':
                $supplierTransactions = TransactionLine::whereHas('transaction', function ($query) use ($businessId, $endOfDay) {
                    $query->where('business_id', $businessId)
                        ->where('created_at', '<=', $endOfDay);
                })
                    ->whereHas('ledger', function ($query) {
                        $query->where('ledger_type', 'Sundry Creditors (Supplier)');
                    })
                    ->get();

                return $supplierTransactions->sum('credit_amount') - $supplierTransactions->sum('debit_amount');

            case 'income':
                $incomeTransactions = TransactionLine::whereHas('transaction', function ($query) use ($businessId, $endOfDay) {
                    $query->where('business_id', $businessId)
                        ->where('created_at', '<=', $endOfDay);
                })
                    ->whereHas('ledger', function ($query) {
                        $query->where('ledger_type', 'Incomes');
                    })
                    ->get();

                return $incomeTransactions->sum('credit_amount') - $incomeTransactions->sum('debit_amount');

            case 'expense':
                $expenseTransactions = TransactionLine::whereHas('transaction', function ($query) use ($businessId, $endOfDay) {
                    $query->where('business_id', $businessId)
                        ->where('created_at', '<=', $endOfDay);
                })
                    ->whereHas('ledger', function ($query) {
                        $query->where('ledger_type', 'Expenses');
                    })
                    ->get();

                return $expenseTransactions->sum('debit_amount') - $expenseTransactions->sum('credit_amount');

            case 'profit_loss':
                // UPDATED: Use stock summary method for profit/loss calculation
                $products = Product::where('business_id', $businessId)
                    ->with([
                        'inventoryTransactionLines' => function ($query) use ($date) {
                            $query->whereDate('created_at', '<=', $date)
                                ->with('inventoryTransaction', 'batch');
                        },
                        'returnedProducts' => function ($query) use ($date) {
                            $query->whereDate('return_date', '<=', $date)
                                ->with('batch');
                        },
                        'damageTransactionLines' => function ($query) use ($date) {
                            $query->whereHas('damageTransaction', function ($q) use ($date) {
                                $q->whereDate('transaction_date', '<=', $date);
                            });
                        }
                    ])
                    ->get();

                $totalGrossProfit = 0;
                foreach ($products as $product) {
                    $salesLines = $product->inventoryTransactionLines
                        ->where('inventoryTransaction.entry_type', 'sale');

                    $salesValue = $salesLines->sum(function ($line) {
                        return $line->quantity * ($line->batch ? $line->batch->trade_price : 0);
                    });

                    $cogs = $salesLines->sum(function ($line) {
                        return $line->quantity * $line->dealer_price;
                    });

                    $totalGrossProfit += ($salesValue - $cogs);
                }

                return $totalGrossProfit;

            default:
                return 0;
        }
    }

    /**
     * Get dashboard data for real-time updates
     */
    public function dashboardData(Request $request)
    {
        try {
            $businessId = null;
            if (Auth::user()->roles->contains('name', 'staff')) {
                $staff = Staff::where('user_id', Auth::id())->first();
                $businessId = $staff->business_id;
            } elseif (Auth::user()->roles->contains('name', 'admin')) {
                $businessId = BusinessAdmin::where('user_id', Auth::id())->first()->business_id;
            }

            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            // Get current totals
            $totalSales = InventoryTransaction::where('business_id', $businessId)
                ->where('entry_type', 'sale')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('grand_total');

            $totalPurchase = InventoryTransaction::where('business_id', $businessId)
                ->where('entry_type', 'purchase')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('grand_total');

            $salesReturn = ReturnedProduct::where('business_id', $businessId)
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->sum('total_amount');

            $damageReturn = DamageTransactionLine::whereHas('damageTransaction', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->sum('total_value');

            $ledgers = Ledger::where('business_id', $businessId)->get();

            $cashInHand = $ledgers->where('ledger_type', 'Cash-in-Hand')->sum('current_balance');
            $bankBalance = $ledgers->where('ledger_type', 'Bank Accounts')->sum('current_balance');
            $customerDue = $ledgers->where('ledger_type', 'Sundry Debtors (Customer)')->sum('current_balance');
            $supplierDue = $ledgers->where('ledger_type', 'Sundry Creditors (Supplier)')->sum('current_balance');
            $totalIncome = $ledgers->where('ledger_type', 'Incomes')->sum('current_balance');
            $totalExpense = $ledgers->where('ledger_type', 'Expenses')->sum('current_balance');

            // UPDATED: Calculate DSR collection instead of customer due
            $totalCollection = $this->calculateDSRCollection($businessId);

            // UPDATED: Use stock summary method for profit/loss
            $products = Product::where('business_id', $businessId)
                ->with([
                    'inventoryTransactionLines' => function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                            ->with('inventoryTransaction', 'batch');
                    },
                    'returnedProducts' => function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('return_date', [$startDate, $endDate])
                            ->with('batch');
                    },
                    'damageTransactionLines' => function ($query) use ($startDate, $endDate) {
                        $query->whereHas('damageTransaction', function ($q) use ($startDate, $endDate) {
                            $q->whereBetween('transaction_date', [$startDate, $endDate]);
                        });
                    }
                ])
                ->get();

            $netProfitLoss = 0;
            foreach ($products as $product) {
                $salesLines = $product->inventoryTransactionLines
                    ->where('inventoryTransaction.entry_type', 'sale');

                $salesValue = $salesLines->sum(function ($line) {
                    return $line->quantity * ($line->batch ? $line->batch->trade_price : 0);
                });

                $cogs = $salesLines->sum(function ($line) {
                    return $line->quantity * $line->dealer_price;
                });

                $netProfitLoss += ($salesValue - $cogs);
            }

            return response()->json([
                'totalSales' => $totalSales,
                'totalPurchase' => $totalPurchase,
                'salesReturn' => $salesReturn,
                'damageReturn' => $damageReturn,
                'cashInHand' => $cashInHand,
                'bankBalance' => $bankBalance,
                'customerDue' => $customerDue,
                'supplierDue' => $supplierDue,
                'totalIncome' => $totalIncome,
                'totalExpense' => $totalExpense,
                'totalCollection' => $totalCollection,
                'netProfitLoss' => $netProfitLoss
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error fetching dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }
}
