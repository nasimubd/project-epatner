<div class="p-4">
    <h3 class="text-lg font-semibold mb-4">Category-wise Sales Breakdown</h3>
    <p class="text-sm text-gray-600 mb-4">
        Period:
        @if(isset($startDate) && is_string($startDate))
        {{ date('M d, Y', strtotime($startDate)) }}
        @else
        {{ now()->startOfMonth()->format('M d, Y') }}
        @endif
        to
        @if(isset($endDate) && is_string($endDate))
        {{ date('M d, Y', strtotime($endDate)) }}
        @else
        {{ now()->format('M d, Y') }}
        @endif
    </p>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @php $totalAmount = $categories->sum('total_sales'); @endphp
                @foreach($categories as $category)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $category['name'] }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ number_format($category['total_quantity']) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">৳{{ number_format($category['total_sales'], 2) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                        {{ $totalAmount > 0 ? number_format(($category['total_sales'] / $totalAmount) * 100, 1) : '0' }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ number_format($categories->sum('total_quantity')) }}</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">৳{{ number_format($totalAmount, 2) }}</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">100%</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>