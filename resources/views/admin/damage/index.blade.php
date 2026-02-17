@extends('admin.layouts.app')

@section('content')
<div class="p-2 sm:p-4">
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="text-xl font-semibold text-gray-800">Damage Transactions</h2>
                <a href="{{ route('admin.inventory.damage_transactions.store') }}"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
                    <span class="mr-2">+</span>
                    New Damage
                </a>
            </div>
        </div>

        <!-- Mobile View -->
        <div class="block sm:hidden">
            @foreach($damages as $damage)
            <div class="border-b border-gray-200 p-4">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="font-medium">{{ $damage->customer->name }}</h3>
                        <p class="text-sm text-gray-600">{{ $damage->transaction_date }}</p>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="text-sm font-semibold">
                            {{ number_format($damage->lines->sum('total_value'), 2) }}
                        </span>
                        <span class="calculated-value hidden text-xs text-green-600" id="calculated-value-mobile-{{ $damage->id }}"></span>
                    </div>
                </div>

                <div class="flex justify-between items-center mb-2">
                    <button type="button" class="text-blue-600 text-sm show-products"
                        data-damage-id="{{ $damage->id }}">
                        Show {{ $damage->lines->count() }} Products
                    </button>

                    <div class="relative">
                        <form action="{{ route('admin.damage.toggle-status', $damage) }}" method="POST" class="status-form" data-damage-id="{{ $damage->id }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="calculated_total" class="calculated-total-input" value="">
                            <input type="hidden" name="batch_selections" class="batch-selections-input" value="">
                            <div class="flex items-center space-x-2">
                                <select name="status" class="status-select text-sm rounded px-2 py-1 border 
                {{ $damage->status === 'approved' ? 'bg-green-100 text-green-800 border-green-300' : 
                   ($damage->status === 'rejected' ? 'bg-red-100 text-red-800 border-red-300' : 
                   'bg-yellow-100 text-yellow-800 border-yellow-300') }}">
                                    <option value="pending" {{ $damage->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ $damage->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ $damage->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                                <button type="button" class="update-status-btn px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded">
                                    Update
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="flex justify-between items-center mt-3 pt-2 border-t border-gray-100">
                    <div class="flex space-x-2">
                        <a href="{{ route('admin.damage.show', $damage) }}" class="text-blue-600 text-sm">View</a>
                        <a href="{{ route('admin.damage.edit', $damage) }}" class="text-indigo-600 text-sm">Edit</a>
                    </div>
                    <form action="{{ route('admin.damage.destroy', $damage) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this damage record?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 text-sm">Delete</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Desktop View -->
        <div class="hidden sm:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Products</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Value</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($damages as $damage)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm">{{ $damage->transaction_date }}</td>
                        <td class="px-4 py-3 text-sm">{{ $damage->customer->name }}</td>
                        <td class="px-4 py-3 text-sm">
                            <button type="button" class="text-blue-600 hover:text-blue-900 show-products"
                                data-damage-id="{{ $damage->id }}">
                                Show {{ $damage->lines->count() }} Products
                            </button>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="original-value">{{ number_format($damage->lines->sum('total_value'), 2) }}</span>
                            <span class="calculated-value hidden" id="calculated-value-{{ $damage->id }}"></span>
                        </td>
                        <td class="px-4 py-3">
                            <form action="{{ route('admin.damage.toggle-status', $damage) }}" method="POST" class="status-form" data-damage-id="{{ $damage->id }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="calculated_total" class="calculated-total-input" value="">
                                <input type="hidden" name="batch_selections" class="batch-selections-input" value="">
                                <div class="flex items-center space-x-2">
                                    <select name="status" class="status-select text-sm rounded px-2 py-1 border 
                {{ $damage->status === 'approved' ? 'bg-green-100 text-green-800 border-green-300' : 
                   ($damage->status === 'rejected' ? 'bg-red-100 text-red-800 border-red-300' : 
                   'bg-yellow-100 text-yellow-800 border-yellow-300') }}">
                                        <option value="pending" {{ $damage->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="approved" {{ $damage->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="rejected" {{ $damage->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    </select>
                                    <button type="button" class="update-status-btn px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded">
                                        Update
                                    </button>
                                </div>
                            </form>
                        </td>


                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('admin.damage.show', $damage) }}" class="text-blue-600 hover:text-blue-900">View</a>
                            <a href="{{ route('admin.damage.edit', $damage) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>

                            <form action="{{ route('admin.damage.destroy', $damage) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this damage record?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-gray-200">
            {{ $damages->links() }}
        </div>
    </div>
</div>

<!-- Products Modal -->
<div id="productsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl max-h-[80vh] overflow-y-auto m-4">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Damaged Products</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700" id="closeProductsModal">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-4" id="productsModalContent">
            <!-- Products will be loaded here -->
            <div class="text-center py-4">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
                <p class="mt-2 text-gray-600">Loading products...</p>
            </div>
        </div>
    </div>
</div>

<!-- Batches Modal -->
<div id="batchesModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl max-h-[80vh] overflow-y-auto m-4">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800" id="batchesModalTitle">Product Batches</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700" id="closeBatchesModal">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-4" id="batchesModalContent">
            <!-- Batches will be loaded here -->
            <div class="text-center py-4">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
                <p class="mt-2 text-gray-600">Loading batches...</p>
            </div>
        </div>
        <div class="p-4 border-t border-gray-200 flex justify-end">
            <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md" id="saveBatchSelection">
                Save Selection
            </button>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Global variables to store state
        let currentDamageId = null;
        let currentProductId = null;
        let batchSelections = {};
        let calculatedTotals = {};

        // Handle update status button clicks
        document.querySelectorAll('.update-status-btn').forEach(button => {
            button.addEventListener('click', function() {
                const form = this.closest('.status-form');
                const damageId = form.dataset.damageId;
                const statusSelect = form.querySelector('.status-select');
                const newStatus = statusSelect.value;

                if (newStatus === 'approved') {
                    // Check if we have batch selections for this damage
                    if (!batchSelections[damageId] || Object.keys(batchSelections[damageId]).length === 0) {
                        alert('Please select batches for the damaged products before approving');
                        statusSelect.value = 'pending'; // Reset to pending
                        return;
                    }

                    // Set the calculated total and batch selections in the form
                    form.querySelector('.calculated-total-input').value = calculatedTotals[damageId] || 0;
                    form.querySelector('.batch-selections-input').value = JSON.stringify(batchSelections[damageId]);

                    // Confirm before submitting
                    if (confirm('Approve this transaction? This will debit supplier ledgers based on the selected batches.')) {
                        form.submit();
                    } else {
                        statusSelect.value = 'pending'; // Reset to pending if cancelled
                    }
                } else if (newStatus === 'rejected') {
                    if (confirm('Are you sure you want to reject this damage transaction?')) {
                        form.submit();
                    } else {
                        statusSelect.value = 'pending'; // Reset to pending if cancelled
                    }
                } else {
                    // For pending status, just submit
                    form.submit();
                }
            });
        });


        // Show products modal
        document.querySelectorAll('.show-products').forEach(button => {
            button.addEventListener('click', function() {
                const damageId = this.dataset.damageId;
                currentDamageId = damageId;

                // Show modal with loading state
                document.getElementById('productsModal').classList.remove('hidden');

                // Fetch products for this damage transaction
                fetch(`/damage/${damageId}/products`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayProducts(data.products, damageId);
                        } else {
                            document.getElementById('productsModalContent').innerHTML = `
                                <p class="text-center text-red-500">Failed to load products</p>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('productsModalContent').innerHTML = `
                            <p class="text-center text-red-500">An error occurred while loading products</p>
                        `;
                    });
            });
        });

        // Close products modal
        document.getElementById('closeProductsModal').addEventListener('click', function() {
            document.getElementById('productsModal').classList.add('hidden');
        });

        // Close batches modal
        document.getElementById('closeBatchesModal').addEventListener('click', function() {
            document.getElementById('batchesModal').classList.add('hidden');
        });

        // Save batch selection
        document.getElementById('saveBatchSelection').addEventListener('click', function() {
            const selectedBatchId = document.querySelector('input[name="batch_id"]:checked')?.value;
            const selectedBatchDpPrice = document.querySelector('input[name="batch_id"]:checked')?.dataset.dpPrice;
            const quantity = document.querySelector('input[name="batch_id"]:checked')?.dataset.quantity;

            if (!selectedBatchId) {
                alert('Please select a batch');
                return;
            }

            // Store the selection
            if (!batchSelections[currentDamageId]) {
                batchSelections[currentDamageId] = {};
            }

            batchSelections[currentDamageId][currentProductId] = {
                batch_id: selectedBatchId,
                dp_price: selectedBatchDpPrice,
                quantity: quantity
            };

            // Recalculate total
            calculateTotal(currentDamageId);

            // Close the modal
            document.getElementById('batchesModal').classList.add('hidden');

            // Update the product row to show it has a batch selected
            const productRow = document.querySelector(`#product-row-${currentProductId}`);
            if (productRow) {
                const batchCell = productRow.querySelector('.batch-cell');
                if (batchCell) {
                    batchCell.innerHTML = `<span class="text-green-600">Batch Selected</span>`;
                }
            }
        });

        // Function to display products in the modal
        function displayProducts(products, damageId) {
            const content = document.getElementById('productsModalContent');
            content.innerHTML = '';

            if (products.length === 0) {
                content.innerHTML = '<p class="text-center text-gray-500">No products found</p>';
                return;
            }

            // Create responsive container
            const container = document.createElement('div');
            container.className = 'overflow-x-auto';

            const table = document.createElement('table');
            table.className = 'min-w-full divide-y divide-gray-200';

            // Create table header
            const thead = document.createElement('thead');
            thead.className = 'bg-gray-50';
            thead.innerHTML = `
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                </tr>
            `;
            table.appendChild(thead);

            // Create table body
            const tbody = document.createElement('tbody');
            tbody.className = 'bg-white divide-y divide-gray-200';

            products.forEach(product => {
                const tr = document.createElement('tr');
                tr.id = `product-row-${product.id}`;
                tr.className = 'hover:bg-gray-50';

                // Check if this product has a batch selected
                const hasSelectedBatch = batchSelections[damageId] && batchSelections[damageId][product.id];

                tr.innerHTML = `
                    <td class="px-4 py-3 text-sm text-gray-900">${product.name}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${product.quantity}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${parseFloat(product.unit_price).toFixed(2)}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${parseFloat(product.total_value).toFixed(2)}</td>
                    <td class="px-4 py-3 text-sm text-gray-900 batch-cell">
                        ${hasSelectedBatch ? 
                            '<span class="text-green-600">Batch Selected</span>' : 
                            '<button type="button" class="text-blue-600 hover:text-blue-900 select-batch" data-product-id="' + product.id + '" data-quantity="' + product.quantity + '">Select Batch</button>'
                        }
                    </td>
                `;

                tbody.appendChild(tr);
            });

            table.appendChild(tbody);
            container.appendChild(table);
            content.appendChild(container);

            // Add event listeners to the select batch buttons
            document.querySelectorAll('.select-batch').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    const quantity = this.dataset.quantity;
                    currentProductId = productId;

                    // Show batches modal with loading state
                    document.getElementById('batchesModal').classList.remove('hidden');

                    // Fetch batches for this product
                    fetch(`/damage/batches/${productId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                displayBatches(data.batches, data.product_name, quantity);
                            } else {
                                document.getElementById('batchesModalContent').innerHTML = `
                                    <p class="text-center text-red-500">Failed to load batches</p>
                                `;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            document.getElementById('batchesModalContent').innerHTML = `
                                <p class="text-center text-red-500">An error occurred while loading batches</p>
                            `;
                        });
                });
            });
        }

        // Function to display batches in the modal
        function displayBatches(batches, productName, quantity) {
            const content = document.getElementById('batchesModalContent');
            document.getElementById('batchesModalTitle').textContent = `Batches for ${productName}`;
            content.innerHTML = '';

            if (batches.length === 0) {
                content.innerHTML = '<p class="text-center text-gray-500">No batches found for this product</p>';
                return;
            }

            // Create responsive container
            const container = document.createElement('div');
            container.className = 'overflow-x-auto';

            const table = document.createElement('table');
            table.className = 'min-w-full divide-y divide-gray-200';

            // Create table header
            const thead = document.createElement('thead');
            thead.className = 'bg-gray-50';
            thead.innerHTML = `
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Select</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch #</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">DP Price</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Available Qty</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry Date</th>
                </tr>
            `;
            table.appendChild(thead);

            // Create table body
            const tbody = document.createElement('tbody');
            tbody.className = 'bg-white divide-y divide-gray-200';

            batches.forEach((batch, index) => {
                // Ensure dp_price is a valid number
                const dpPrice = parseFloat(batch.dp_price) || 0;

                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50';

                tr.innerHTML = `
                    <td class="px-4 py-3 text-sm text-gray-900">
                        <input type="radio" name="batch_id" value="${batch.id}" data-dp-price="${dpPrice}" data-quantity="${quantity}" ${index === 0 ? 'checked' : ''}>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900">${batch.batch_number}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${dpPrice.toFixed(2)}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${batch.remaining_quantity}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${batch.expiry_date}</td>
                `;

                tbody.appendChild(tr);
            });

            table.appendChild(tbody);
            container.appendChild(table);
            content.appendChild(container);

            // Get the first batch's dp_price (with fallback to 0)
            const firstBatchDpPrice = parseFloat(batches[0]?.dp_price) || 0;
            const parsedQuantity = parseFloat(quantity) || 0;
            const totalValue = (firstBatchDpPrice * parsedQuantity).toFixed(2);

            // Add a summary section
            const summary = document.createElement('div');
            summary.className = 'mt-4 p-3 bg-gray-50 rounded';
            summary.innerHTML = `
                <h4 class="font-medium text-gray-700">Selection Summary</h4>
                <p class="text-sm text-gray-600">Damage Quantity: ${quantity}</p>
                <p class="text-sm text-gray-600">Selected Batch DP Price: <span id="selected-dp-price">${firstBatchDpPrice.toFixed(2)}</span></p>
                <p class="text-sm font-medium text-gray-700">Total Value: <span id="selected-total-value">${totalValue}</span></p>
            `;
            content.appendChild(summary);

            // Add event listeners to update the summary when a different batch is selected
            document.querySelectorAll('input[name="batch_id"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const dpPrice = parseFloat(this.dataset.dpPrice) || 0;
                    const qty = parseFloat(this.dataset.quantity) || 0;
                    const totalValue = (dpPrice * qty).toFixed(2);

                    document.getElementById('selected-dp-price').textContent = dpPrice.toFixed(2);
                    document.getElementById('selected-total-value').textContent = totalValue;
                });
            });
        }

        // Function to calculate the total value based on batch selections
        function calculateTotal(damageId) {
            if (!batchSelections[damageId]) {
                return;
            }

            let total = 0;

            // Sum up the values from all selected batches
            for (const productId in batchSelections[damageId]) {
                const selection = batchSelections[damageId][productId];
                const dpPrice = parseFloat(selection.dp_price) || 0;
                const quantity = parseFloat(selection.quantity) || 0;
                total += dpPrice * quantity;
            }

            // Store the calculated total
            calculatedTotals[damageId] = total;

            // Update the display in desktop view
            const calculatedValueElement = document.getElementById(`calculated-value-${damageId}`);
            const originalValueElement = calculatedValueElement?.previousElementSibling;

            if (calculatedValueElement) {
                calculatedValueElement.textContent = `${total.toFixed(2)} (DP Price)`;
                calculatedValueElement.classList.remove('hidden');
                if (originalValueElement) {
                    originalValueElement.classList.add('line-through', 'text-gray-400');
                }
            }

            // Update the display in mobile view
            const mobileCalculatedValueElement = document.getElementById(`calculated-value-mobile-${damageId}`);

            if (mobileCalculatedValueElement) {
                mobileCalculatedValueElement.textContent = `Total (DP): ${total.toFixed(2)}`;
                mobileCalculatedValueElement.classList.remove('hidden');
            }
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const productsModal = document.getElementById('productsModal');
            const batchesModal = document.getElementById('batchesModal');

            if (event.target === productsModal) {
                productsModal.classList.add('hidden');
            }

            if (event.target === batchesModal) {
                batchesModal.classList.add('hidden');
            }
        });

        // Handle escape key to close modals
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.getElementById('productsModal').classList.add('hidden');
                document.getElementById('batchesModal').classList.add('hidden');
            }
        });

        // Apply color styling to status selects based on their value
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                // Remove all existing color classes
                this.classList.remove(
                    'bg-green-100', 'text-green-800', 'border-green-300',
                    'bg-yellow-100', 'text-yellow-800', 'border-yellow-300',
                    'bg-red-100', 'text-red-800', 'border-red-300'
                );

                // Add appropriate color classes based on selected value
                if (this.value === 'approved') {
                    this.classList.add('bg-green-100', 'text-green-800', 'border-green-300');
                } else if (this.value === 'rejected') {
                    this.classList.add('bg-red-100', 'text-red-800', 'border-red-300');
                } else {
                    this.classList.add('bg-yellow-100', 'text-yellow-800', 'border-yellow-300');
                }
            });
        });
    });
</script>

@endpush
@endsection