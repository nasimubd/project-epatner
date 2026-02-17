@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto">
    <!-- Action buttons outside invoice -->
    <div class="flex justify-center mb-4 print:hidden">
        <div class="flex gap-2">
            <a href="{{ route('admin.inventory.inventory_transactions.index') }}"
                class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L4.414 9H17a1 1 0 110 2H4.414l5.293 5.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Back
            </a>
            @role('admin')
            <button onclick="printReceipt()"
                class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">Print</button>
            @endrole
            @if($inventoryTransaction->paid_amount > 0)
            <button id="deleteCollectionBtn"
                class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600"
                data-id="{{ $inventoryTransaction->id }}"
                data-paid-amount="{{ $inventoryTransaction->paid_amount }}">
                Delete Collection
            </button>
            @endif
            @if($returnedProducts->count() > 0)
            <button id="deleteReturnBtn"
                class="bg-orange-500 text-white px-4 py-2 rounded-md hover:bg-orange-600"
                data-id="{{ $inventoryTransaction->id }}"
                data-returned-total="{{ $returnedTotal }}">
                Delete Return
            </button>
            @endif
        </div>
    </div>

    <!-- Thermal Invoice -->
    <div class="w-[80mm] mx-auto p-2 bg-white print:p-0 print:shadow-none shadow">
        <!-- Invoice Content with added padding -->
        <div class="px-3">
            <!-- Business Header -->
            <div class="text-center border-b-2 border-dashed border-black pb-2">
                @if($business)
                <h2 class="font-bold text-xl">{{ $business->name }}</h2>
                <p class="text-sm">{{ $business->address }}</p>
                <p class="text-sm">+88{{ $business->contact_number }}</p>
                @endif
                @if($staffName)
                <p class="text-sm">By: {{ $staffName }}</p>
                @endif
            </div>

            <!-- Customer Info -->
            <div class="mb-2 text-center border-b-2 border-dashed border-black pb-2 pt-1">
                <div class="mb-1">
                    <p class="text-sm font-semibold mb-0.5">{{ $customer->name }}</p>
                    @if($customer->location)
                    <p class="text-xs mb-0.5">{{ $customer->location }}</p>
                    @endif
                    @if($customer->contact)
                    <p class="text-xs mb-0.5">{{ $customer->contact }}</p>
                    @endif
                </div>

                <div class="text-xs">
                    <p class="mb-0.5">
                        <span class="font-bold">Invoice #:</span>{{ $inventoryTransaction->id }}
                    </p>
                    <p class="mb-0.5">
                        <span class="font-bold">Date:</span>{{ \Carbon\Carbon::parse($inventoryTransaction->transaction_date)->timezone('Asia/Dhaka')->format('F d Y - h:i A') }}
                    </p>
                    <p>
                        <span class="font-bold">Payment:</span>{{ ucfirst($inventoryTransaction->payment_method) }}
                    </p>
                </div>
            </div>

            <!-- Products List -->
            <div class="text-sm">
                <h3 class="font-bold text-sm mb-1">Products</h3>

                @php
                $displayedLines = collect();
                @endphp

                @foreach($inventoryTransaction->lines as $line)
                @if(!$line->is_damaged && !$displayedLines->contains('product_id', $line->product_id))
                <div class="py-1">
                    <div class="font-semibold text-xs">{{ $line->product->name }}</div>
                    <div class="flex justify-between text-xs">
                        <span>
                            {{ $line->product->unit->type === 'unit' ? (floor($line->quantity) == $line->quantity ? number_format($line->quantity, 0) : number_format($line->quantity, 2)) : (floor($line->quantity) == $line->quantity ? number_format($line->quantity, 0) : number_format($line->quantity, 2)) }}
                            × ৳{{ floor($line->unit_price) == $line->unit_price ? number_format($line->unit_price, 0) : number_format($line->unit_price, 2) }}
                        </span>
                        <span>৳{{ floor($line->line_total) == $line->line_total ? number_format($line->line_total, 0) : number_format($line->line_total, 2) }}</span>
                    </div>
                </div>
                @php
                $displayedLines->push($line);
                @endphp
                @endif
                @endforeach
            </div>

            @if($damagedProducts->count() > 0)
            <div class="border-b-2 border-dashed border-black my-2"></div>
            <div class="mb-2">
                <h3 class="font-bold text-sm">Damaged Products</h3>
                <div class="text-sm">
                    @foreach($damagedProducts as $damaged)
                    <div class="py-1">
                        <div class="font-semibold text-xs">{{ $damaged->product->name }}</div>
                        <div class="flex justify-between text-xs">
                            <span>
                                {{ $damaged->product->unit->type === 'unit' ? (floor($damaged->quantity) == $damaged->quantity ? number_format($damaged->quantity, 0) : number_format($damaged->quantity, 2)) : (floor($damaged->quantity) == $damaged->quantity ? number_format($damaged->quantity, 0) : number_format($damaged->quantity, 2)) }}
                                × ৳{{ floor($damaged->unit_price) == $damaged->unit_price ? number_format($damaged->unit_price, 0) : number_format($damaged->unit_price, 2) }}
                            </span>
                            <span>৳{{ floor($damaged->total_value) == $damaged->total_value ? number_format($damaged->total_value, 0) : number_format($damaged->total_value, 2) }}</span>
                        </div>
                    </div>
                    @endforeach

                    <div class="flex justify-between font-bold text-sm pt-1">
                        <span>Damage Total:</span>
                        <span>৳{{ floor($damagedTotal) == $damagedTotal ? number_format($damagedTotal, 0) : number_format($damagedTotal, 2) }}</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- After Damaged Products Section -->
            @if($returnedProducts->count() > 0)
            <div class="border-b-2 border-dashed border-black my-2"></div>
            <div class="mb-2">
                <h3 class="font-bold text-sm">Returned Products</h3>
                <div class="text-sm">
                    @foreach($returnedProducts as $returned)
                    <div class="py-1">
                        <div class="font-semibold text-xs">{{ $returned->product->name }}</div>
                        <div class="flex justify-between text-xs">
                            <span>
                                {{ $returned->product->unit->type === 'unit' ? (floor($returned->quantity) == $returned->quantity ? number_format($returned->quantity, 0) : number_format($returned->quantity, 2)) : (floor($returned->quantity) == $returned->quantity ? number_format($returned->quantity, 0) : number_format($returned->quantity, 2)) }}
                                × ৳{{ floor($returned->unit_price) == $returned->unit_price ? number_format($returned->unit_price, 0) : number_format($returned->unit_price, 2) }}
                            </span>
                            <span>৳{{ floor($returned->total_amount) == $returned->total_amount ? number_format($returned->total_amount, 0) : number_format($returned->total_amount, 2) }}</span>
                        </div>
                    </div>
                    @endforeach

                    <div class="flex justify-between font-bold text-sm pt-1">
                        <span>Return Total:</span>
                        <span>৳{{ floor($returnedTotal) == $returnedTotal ? number_format($returnedTotal, 0) : number_format($returnedTotal, 2) }}</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Single separator before totals -->
            <div class="border-b-2 border-dashed border-black my-2"></div>

            <!-- Update Totals Section -->
            <div class="pt-2">
                <div class="flex justify-between text-sm">
                    <span>Subtotal:</span>
                    <span>৳{{ floor($inventoryTransaction->subtotal) == $inventoryTransaction->subtotal ? number_format($inventoryTransaction->subtotal, 0) : number_format($inventoryTransaction->subtotal, 2) }}</span>
                </div>

                @if($inventoryTransaction->paid_amount > 0)
                <div class="flex justify-between text-sm">
                    <span>Paid Amount:</span>
                    <span class="text-green-600">৳{{ floor($inventoryTransaction->paid_amount) == $inventoryTransaction->paid_amount ? number_format($inventoryTransaction->paid_amount, 0) : number_format($inventoryTransaction->paid_amount, 2) }}</span>
                </div>
                @endif

                <div class="flex justify-between font-bold text-sm">
                    <span>Grand Total:</span>
                    <span>৳{{ number_format(round($inventoryTransaction->grand_total)) }}
                        @php
                        $fraction = $inventoryTransaction->grand_total - floor($inventoryTransaction->grand_total);
                        @endphp
                        @if($fraction > 0)
                        <span class="text-xs text-gray-500">({{ number_format($fraction, 2) }})</span>
                        @endif
                    </span>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-4 text-sm border-t-2 border-dashed border-black pt-2">
                <p>Thank You and See You Again!</p>
                <p class="text-xs mt-2">Powered By <span class="font-bold text-blue-600">ePATNER</span></p>
            </div>
        </div>
    </div>
</div>

<style>
    @page {
        margin: 0;
        size: 80mm auto;
    }

    body {
        margin: 0;
        padding: 0;
    }

    .print-hidden {
        display: none !important;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        .w-\[80mm\] {
            visibility: visible;
            position: absolute;
            left: 0;
            top: 0;
            width: 80mm;
        }

        .w-\[80mm\] * {
            visibility: visible;
        }
    }
</style>

@push('styles')
<!-- Preconnect to CDN domains -->
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

<script>
    function printReceipt() {
        window.print();
    }

    // Add these click handlers in your script section
    document.addEventListener('DOMContentLoaded', function() {
        // Edit handler
        document.querySelector('.edit-btn').addEventListener('click', function() {
            const transactionId = this.getAttribute('data-id');
            window.location.href = `/admin/inventory/inventory_transactions/${transactionId}/edit`;
        });
    });

    // Add delete collection functionality
    document.addEventListener('DOMContentLoaded', function() {
        const deleteCollectionBtn = document.getElementById('deleteCollectionBtn');

        if (deleteCollectionBtn) {
            deleteCollectionBtn.addEventListener('click', function() {
                const transactionId = this.getAttribute('data-id');
                const paidAmount = parseFloat(this.getAttribute('data-paid-amount'));

                // Confirm deletion
                Swal.fire({
                    title: 'Delete Collection?',
                    text: `This will delete the collection of ৳${paidAmount.toFixed(2)} and add it back to the grand total. This action cannot be undone!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait while we delete the collection',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Send delete request
                        fetch(`/admin/inventory/inventory_transactions/${transactionId}/delete-collection`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: data.message,
                                        icon: 'success'
                                    }).then(() => {
                                        // Reload the page to reflect changes
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: data.message,
                                        icon: 'error'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Delete collection error:', error);
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'An unexpected error occurred. Please try again.',
                                    icon: 'error'
                                });
                            });
                    }
                });
            });
        }

        // Add delete return functionality
        const deleteReturnBtn = document.getElementById('deleteReturnBtn');

        if (deleteReturnBtn) {
            deleteReturnBtn.addEventListener('click', function() {
                const transactionId = this.getAttribute('data-id');
                const returnedTotal = parseFloat(this.getAttribute('data-returned-total'));

                // Confirm deletion
                Swal.fire({
                    title: 'Delete Return Entry?',
                    html: `
                <div class="text-left">
                    <p class="mb-2">This will permanently delete the return entry of <strong>৳${returnedTotal.toFixed(2)}</strong> and:</p>
                    <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                        <li>Remove returned quantities from inventory</li>
                        <li>Restore original transaction line quantities</li>
                        <li>Delete all related accounting entries</li>
                        <li>Add the amount back to grand total</li>
                    </ul>
                    <p class="mt-3 text-red-600 font-medium">This action cannot be undone!</p>
                </div>
            `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    width: '500px'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait while we delete the return entry',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Send delete request
                        fetch(`/admin/inventory/inventory_transactions/${transactionId}/delete-return`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: data.message,
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        // Reload the page to reflect changes
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: data.message,
                                        icon: 'error'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Delete return error:', error);
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'An unexpected error occurred. Please try again.',
                                    icon: 'error'
                                });
                            });
                    }
                });
            });
        }
    });
</script>
@endsection