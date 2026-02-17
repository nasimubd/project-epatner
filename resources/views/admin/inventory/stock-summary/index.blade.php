<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Report - {{ $business->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>

<body class="bg-gray-50">
    <!-- Header Section -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">

            <form action="{{ route('admin.inventory.stock-summary') }}" method="GET" class="bg-white p-4 rounded-lg shadow mb-6">
                <div class="flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4">
                    <div class="flex-1">
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="{{ $startDate }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div class="flex-1">
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="{{ $endDate }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <div class="flex-none">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Filter
                        </button>
                    </div>
                </div>
            </form>

            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.dashboard') }}" class="text-gray-500 hover:text-gray-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Stock Summary Report</h1>
                        <p class="text-sm text-gray-600">{{ $business->name }}</p>
                    </div>
                </div>
                <div class="text-sm text-gray-600">
                    {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} -
                    {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8" x-data="stockSummary()">
        @php
        $grandTotals = [
        'purchase_qty' => 0,
        'purchase_value' => 0,
        'sales_qty' => 0,
        'sales_value' => 0,
        'online_sales_qty' => 0,
        'online_sales_value' => 0,
        'return_qty' => 0,
        'return_value' => 0,
        'damage_qty' => 0,
        'damage_value' => 0,
        'gross_profit_amount' => 0,
        'closing_qty' => 0,
        'closing_value' => 0
        ];
        @endphp

        <!-- Category Sections -->
        @foreach($categorizedProducts as $categoryName => $categoryData)
        <div class="mb-6 bg-white rounded-lg shadow">
            <div class="p-4 border-b cursor-pointer" @click="toggleCategory('{{ $categoryName }}')">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $categoryName }}</h3>
                    <div class="flex items-center gap-6">
                        <span class="text-gray-600">Gross Profit: ৳{{ number_format($categoryData['category_totals']['gross_profit_amount'], 2) }}</span>
                        <span class="text-gray-600">Closing Value: {{ number_format($categoryData['category_totals']['closing_value'], 2) }}</span>

                        <svg class="w-5 h-5 text-gray-500 transition-transform"
                            :class="{'rotate-180': expandedCategories['{{ $categoryName }}']}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
            </div>


            <div x-show="expandedCategories['{{ $categoryName }}']"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="p-4">
                <div class="overflow-x-auto">
                    <div class="flex justify-end mb-2 space-x-2">
                        <button
                            @click="sortProductsByValue('{{ $categoryName }}', 'desc')"
                            class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded flex items-center"
                            title="Sort by highest stock value">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                            </svg>
                            Highest Value
                        </button>
                        <button
                            @click="sortProductsByValue('{{ $categoryName }}', 'asc')"
                            class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded flex items-center"
                            title="Sort by lowest stock value">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            Lowest Value
                        </button>
                        <!-- Return metrics buttons -->
                        <button
                            @click="sortProductsByReturnQty('{{ $categoryName }}', 'desc')"
                            class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded flex items-center"
                            title="Sort by highest return quantity">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                            </svg>
                            Highest Return Qty
                        </button>
                        <button
                            @click="sortProductsByReturnValue('{{ $categoryName }}', 'desc')"
                            class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded flex items-center"
                            title="Sort by highest return value">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                            </svg>
                            Highest Return Value
                        </button>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Qty</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Value</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sales Qty</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sales Value</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Online Sales Qty</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Online Sales Value</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Return Qty</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Return Value</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Damage Qty</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Damage Value</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Profit</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Closing Qty</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Closing Value</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" :id="'category-products-{{ $categoryName }}'">
                            @foreach($categoryData['products'] as $product)
                            <tr class="hover:bg-gray-50 product-row" data-closing-qty="{{ $product['closing_qty'] }}" data-closing-value="{{ $product['closing_value'] }}">
                                <td class="px-3 py-2 text-sm text-gray-900">{{ $product['name'] }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($product['purchase_qty'], 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">৳{{ number_format($product['purchase_value'], 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($product['sales_qty'], 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">৳{{ number_format($product['sales_value'], 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($product['online_sales_qty'] ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">৳{{ number_format($product['online_sales_value'] ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($product['return_qty'] ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">৳{{ number_format($product['return_value'] ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($product['damage_qty'] ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">৳{{ number_format($product['damage_value'] ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-sm text-right {{ $product['gross_profit_amount'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ৳{{ number_format($product['gross_profit_amount'], 2) }}
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($product['closing_qty'], 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">৳{{ number_format($product['closing_value'], 2) }}</td>
                            </tr>
                            @endforeach
                            <!-- Category Total Row -->
                            <tr class="bg-gray-50 font-semibold category-total">
                                <td class="px-3 py-2 text-sm text-gray-900">Category Total</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($categoryData['category_totals']['purchase_qty'], 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">৳{{ number_format($categoryData['category_totals']['purchase_value'], 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($categoryData['category_totals']['sales_qty'], 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">৳{{ number_format($categoryData['category_totals']['sales_value'], 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($categoryData['category_totals']['online_sales_qty'] ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">৳{{ number_format($categoryData['category_totals']['online_sales_value'] ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($categoryData['category_totals']['return_qty'] ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">৳{{ number_format($categoryData['category_totals']['return_value'] ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($categoryData['category_totals']['damage_qty'] ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">৳{{ number_format($categoryData['category_totals']['damage_value'] ?? 0, 2) }}</td>
                                <td class="px-3 py-2 text-sm text-right {{ $categoryData['category_totals']['gross_profit_amount'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ৳{{ number_format($categoryData['category_totals']['gross_profit_amount'], 2) }}
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($categoryData['category_totals']['closing_qty'], 2) }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-right">৳{{ number_format($categoryData['category_totals']['closing_value'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @php
        foreach ($grandTotals as $key => $value) {
        $grandTotals[$key] += $categoryData['category_totals'][$key] ?? 0;
        }
        @endphp
        @endforeach

        <!-- Grand Total Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Grand Total</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div class="bg-gray-50 p-3 rounded">
                    <div class="text-sm text-gray-500">Purchase Value</div>
                    <div class="text-lg font-semibold">৳{{ number_format($grandTotals['purchase_value'], 2) }}</div>
                </div>

                <div class="bg-gray-50 p-3 rounded">
                    <div class="text-sm text-gray-500">Sales Value</div>
                    <div class="text-lg font-semibold">৳{{ number_format($grandTotals['sales_value'], 2) }}</div>
                </div>

                <div class="bg-gray-50 p-3 rounded">
                    <div class="text-sm text-gray-500">Online Sales Value</div>
                    <div class="text-lg font-semibold">৳{{ number_format($grandTotals['online_sales_value'], 2) }}</div>
                </div>

                <div class="bg-gray-50 p-3 rounded">
                    <div class="text-sm text-gray-500">Return Value</div>
                    <div class="text-lg font-semibold">৳{{ number_format($grandTotals['return_value'], 2) }}</div>
                </div>

                <div class="bg-gray-50 p-3 rounded">
                    <div class="text-sm text-gray-500">Damage Value</div>
                    <div class="text-lg font-semibold">৳{{ number_format($grandTotals['damage_value'], 2) }}</div>
                </div>

                <div class="bg-gray-50 p-3 rounded">
                    <div class="text-sm text-gray-500">Gross Profit</div>
                    <div class="text-lg font-semibold {{ $grandTotals['gross_profit_amount'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        ৳{{ number_format($grandTotals['gross_profit_amount'], 2) }}
                    </div>
                </div>

                <div class="bg-gray-50 p-3 rounded">
                    <div class="text-sm text-gray-500">Closing Value</div>
                    <div class="text-lg font-semibold">৳{{ number_format($grandTotals['closing_value'], 2) }}</div>
                </div>

                <div class="bg-gray-50 p-3 rounded">
                    <div class="text-sm text-gray-500">Return Quantity</div>
                    <div class="text-lg font-semibold">{{ number_format($grandTotals['return_qty'], 2) }}</div>
                </div>

                <div class="bg-gray-50 p-3 rounded">
                    <div class="text-sm text-gray-500">Damage Quantity</div>
                    <div class="text-lg font-semibold">{{ number_format($grandTotals['damage_qty'], 2) }}</div>
                </div>

                <div class="bg-gray-50 p-3 rounded">
                    <div class="text-sm text-gray-500">Online Sales Quantity</div>
                    <div class="text-lg font-semibold">{{ number_format($grandTotals['online_sales_qty'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function stockSummary() {
            return {
                expandedCategories: {},

                toggleCategory(categoryName) {
                    this.expandedCategories[categoryName] = !this.expandedCategories[categoryName];
                },

                sortProductsByValue(categoryName, direction) {
                    const tbody = document.querySelector(`[id="category-products-${categoryName}"]`);
                    if (!tbody) return;

                    // Get all product rows (excluding the category total row)
                    const rows = Array.from(tbody.querySelectorAll('tr.product-row'));

                    // Get the category total row
                    const totalRow = tbody.querySelector('tr.category-total');

                    // Sort the rows based on closing value
                    rows.sort((a, b) => {
                        const valueA = parseFloat(a.getAttribute('data-closing-value'));
                        const valueB = parseFloat(b.getAttribute('data-closing-value'));

                        return direction === 'asc' ? valueA - valueB : valueB - valueA;
                    });

                    // Clear the tbody
                    while (tbody.firstChild) {
                        tbody.removeChild(tbody.firstChild);
                    }

                    // Add the sorted rows back
                    rows.forEach(row => {
                        tbody.appendChild(row);
                    });

                    // Add the total row back at the end
                    if (totalRow) {
                        tbody.appendChild(totalRow);
                    }
                },

                sortProductsByReturnQty(categoryName, direction) {
                    const tbody = document.querySelector(`[id="category-products-${categoryName}"]`);
                    if (!tbody) return;

                    // Get all product rows (excluding the category total row)
                    const rows = Array.from(tbody.querySelectorAll('tr.product-row'));

                    // Get the category total row
                    const totalRow = tbody.querySelector('tr.category-total');

                    // Sort the rows based on return quantity (8th column, index 7)
                    rows.sort((a, b) => {
                        const cellA = a.querySelectorAll('td')[7];
                        const cellB = b.querySelectorAll('td')[7];

                        const valueA = parseFloat(cellA.textContent.replace(/,/g, ''));
                        const valueB = parseFloat(cellB.textContent.replace(/,/g, ''));

                        return direction === 'asc' ? valueA - valueB : valueB - valueA;
                    });

                    // Clear the tbody
                    while (tbody.firstChild) {
                        tbody.removeChild(tbody.firstChild);
                    }

                    // Add the sorted rows back
                    rows.forEach(row => {
                        tbody.appendChild(row);
                    });

                    // Add the total row back at the end
                    if (totalRow) {
                        tbody.appendChild(totalRow);
                    }
                },

                sortProductsByReturnValue(categoryName, direction) {
                    const tbody = document.querySelector(`[id="category-products-${categoryName}"]`);
                    if (!tbody) return;

                    // Get all product rows (excluding the category total row)
                    const rows = Array.from(tbody.querySelectorAll('tr.product-row'));

                    // Get the category total row
                    const totalRow = tbody.querySelector('tr.category-total');

                    // Sort the rows based on return value (9th column, index 8)
                    rows.sort((a, b) => {
                        const cellA = a.querySelectorAll('td')[8];
                        const cellB = b.querySelectorAll('td')[8];

                        const valueA = parseFloat(cellA.textContent.replace(/,/g, '').replace('৳', ''));
                        const valueB = parseFloat(cellB.textContent.replace(/,/g, '').replace('৳', ''));

                        return direction === 'asc' ? valueA - valueB : valueB - valueA;
                    });

                    // Clear the tbody
                    while (tbody.firstChild) {
                        tbody.removeChild(tbody.firstChild);
                    }

                    // Add the sorted rows back
                    rows.forEach(row => {
                        tbody.appendChild(row);
                    });

                    // Add the total row back at the end
                    if (totalRow) {
                        tbody.appendChild(totalRow);
                    }
                },

                // Add new sorting function for online sales
                sortProductsByOnlineSalesQty(categoryName, direction) {
                    const tbody = document.querySelector(`[id="category-products-${categoryName}"]`);
                    if (!tbody) return;

                    // Get all product rows (excluding the category total row)
                    const rows = Array.from(tbody.querySelectorAll('tr.product-row'));

                    // Get the category total row
                    const totalRow = tbody.querySelector('tr.category-total');

                    // Sort the rows based on online sales quantity (6th column, index 5)
                    rows.sort((a, b) => {
                        const cellA = a.querySelectorAll('td')[5];
                        const cellB = b.querySelectorAll('td')[5];

                        const valueA = parseFloat(cellA.textContent.replace(/,/g, ''));
                        const valueB = parseFloat(cellB.textContent.replace(/,/g, ''));

                        return direction === 'asc' ? valueA - valueB : valueB - valueA;
                    });

                    // Clear the tbody
                    while (tbody.firstChild) {
                        tbody.removeChild(tbody.firstChild);
                    }

                    // Add the sorted rows back
                    rows.forEach(row => {
                        tbody.appendChild(row);
                    });

                    // Add the total row back at the end
                    if (totalRow) {
                        tbody.appendChild(totalRow);
                    }
                },

                // Add new sorting function for online sales value
                sortProductsByOnlineSalesValue(categoryName, direction) {
                    const tbody = document.querySelector(`[id="category-products-${categoryName}"]`);
                    if (!tbody) return;

                    // Get all product rows (excluding the category total row)
                    const rows = Array.from(tbody.querySelectorAll('tr.product-row'));

                    // Get the category total row
                    const totalRow = tbody.querySelector('tr.category-total');

                    // Sort the rows based on online sales value (7th column, index 6)
                    rows.sort((a, b) => {
                        const cellA = a.querySelectorAll('td')[6];
                        const cellB = b.querySelectorAll('td')[6];

                        const valueA = parseFloat(cellA.textContent.replace(/,/g, '').replace('৳', ''));
                        const valueB = parseFloat(cellB.textContent.replace(/,/g, '').replace('৳', ''));

                        return direction === 'asc' ? valueA - valueB : valueB - valueA;
                    });

                    // Clear the tbody
                    while (tbody.firstChild) {
                        tbody.removeChild(tbody.firstChild);
                    }

                    // Add the sorted rows back
                    rows.forEach(row => {
                        tbody.appendChild(row);
                    });

                    // Add the total row back at the end
                    if (totalRow) {
                        tbody.appendChild(totalRow);
                    }
                },

                init() {
                    // Initialize all categories as collapsed by default
                    @foreach($categorizedProducts as $categoryName => $categoryData)
                    this.expandedCategories['{{ $categoryName }}'] = false;
                    @endforeach
                }
            }
        }
    </script>
</body>

</html>