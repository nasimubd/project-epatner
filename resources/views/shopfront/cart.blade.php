<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - {{ $business->name }}</title>
    <meta name="description" content="Your shopping cart at {{ $business->name }}">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>

<body class="bg-gray-50" x-data="{ 
    init() {
        this.cartItems = JSON.parse(localStorage.getItem('shopfrontCart_{{ $shopfront->shopfront_id }}') || '[]');
    },
    cartItems: [],
    customerName: '',
    customerPhone: '',
    location: '',
    isSubmitting: false,
    
    removeItem(index) {
        this.cartItems.splice(index, 1);
        this.updateLocalStorage();
    },
    
    updateQuantity(index, newQuantity) {
        const item = this.cartItems[index];
        
        // Parse quantity as float to handle fractions
        const parsedQuantity = parseFloat(newQuantity);
        
        // Validate quantity
        if (isNaN(parsedQuantity) || parsedQuantity <= 0) {
            this.showNotification('Please enter a valid quantity', 'error');
            return;
        }
        
        // Check stock limits for non-common products
        if (!item.is_common && parsedQuantity > item.max_stock) {
            this.showNotification(`Maximum available stock is ${item.max_stock}`, 'error');
            // Reset to max available stock
            item.quantity = item.max_stock;
        } else {
            item.quantity = parsedQuantity;
        }
        
        this.updateLocalStorage();
    },
    
    incrementQuantity(index) {
        const item = this.cartItems[index];
        const newQuantity = item.quantity + 1;
        
        if (!item.is_common && newQuantity > item.max_stock) {
            this.showNotification(`Maximum available stock is ${item.max_stock}`, 'error');
            return;
        }
        
        item.quantity = newQuantity;
        this.updateLocalStorage();
    },
    
    decrementQuantity(index) {
        const item = this.cartItems[index];
        const newQuantity = Math.max(0.1, item.quantity - 1);
        item.quantity = newQuantity;
        this.updateLocalStorage();
    },
    
    updateLocalStorage() {
        localStorage.setItem('shopfrontCart_{{ $shopfront->shopfront_id }}', JSON.stringify(this.cartItems));
    },
    
    getCartTotal() {
        return this.cartItems.reduce((total, item) => total + (item.price * item.quantity), 0);
    },
    
    getCartCount() {
        return this.cartItems.reduce((count, item) => count + item.quantity, 0);
    },
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        const bgColor = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        }[type] || 'bg-blue-500';

        notification.className = `fixed top-24 right-4 ${bgColor} text-white px-6 py-4 rounded-xl shadow-2xl z-50 max-w-sm transform translate-x-full transition-transform duration-300`;
        notification.innerHTML = `
            <div class='flex items-center space-x-3'>
                <p class='font-medium'>${message}</p>
            </div>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    },
    
    submitOrder() {
        if (this.cartItems.length === 0) {
            this.showNotification('Your cart is empty', 'error');
            return;
        }
        
        if (!this.customerName || !this.customerPhone || !this.location) {
            this.showNotification('Please provide your name, phone number and delivery location', 'error');
            return;
        }
        
        this.isSubmitting = true;
        
        // Prepare form data
        const formData = new FormData();
        formData.append('customer_name', this.customerName);
        formData.append('customer_phone', this.customerPhone);
        formData.append('location', this.location);
        
        // Add products with proper boolean conversion
        this.cartItems.forEach((item, index) => {
            formData.append(`products[${index}][id]`, item.id);
            formData.append(`products[${index}][quantity]`, item.quantity);
            formData.append(`products[${index}][is_common]`, item.is_common === true);
            formData.append(`products[${index}][batch_id]`, item.batch_id || '');
        });

        fetch('{{ route('shopfront.order.store', ['id' => $shopfront->shopfront_id]) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
        })
        .then(data => {
            if (data.redirect_url) {
                // Clear cart after successful order
                this.cartItems = [];
                this.updateLocalStorage();
                window.location.href = data.redirect_url;
            }
        })
        .catch(error => {
            console.error('Detailed Error:', error);
            const errorMessage = error.message || 'There was a problem submitting your order. Please try again.';
            this.showNotification(errorMessage, 'error');
            this.isSubmitting = false;
        });
    }
}">

    <!-- Enhanced Mobile-Friendly Header -->
    <header class="bg-white shadow-sm fixed w-full top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 truncate">{{ $business->name }}</h1>
                    <p class="text-xs sm:text-sm text-gray-600">Shopping Cart</p>
                </div>
                <a href="{{ route('shopfront.show', ['id' => $shopfront->shopfront_id]) }}"
                    class="flex items-center text-blue-600 hover:text-blue-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                    <span class="hidden sm:inline">Continue Shopping</span>
                    <span class="inline sm:hidden">Back</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content with Enhanced Mobile Layout -->
    <main class="container mx-auto px-4 py-6 mt-20">
        <div class="max-w-4xl mx-auto">
            <!-- Cart Items with Improved Mobile Layout -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                <div class="p-4 sm:p-6">
                    <h2 class="text-lg sm:text-xl font-semibold mb-4 sm:mb-6 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Your Cart (<span x-text="getCartCount()"></span> items)
                    </h2>

                    <!-- Empty Cart State with Enhanced Visual Appeal -->
                    <div x-show="cartItems.length === 0" class="text-center py-8 sm:py-12">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 sm:h-20 sm:w-20 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Your cart is empty</h3>
                        <p class="text-gray-500 mb-6">Looks like you haven't added any products yet.</p>
                        <a href="{{ route('shopfront.show', ['id' => $shopfront->shopfront_id]) }}"
                            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-md transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            Start Shopping
                        </a>
                    </div>

                    <!-- Enhanced Cart Items List for Mobile -->
                    <div x-show="cartItems.length > 0" class="divide-y divide-gray-200">
                        <template x-for="(item, index) in cartItems" :key="index">
                            <div class="py-4 sm:py-6">
                                <div class="flex flex-col sm:flex-row sm:items-center">
                                    <!-- Product Info -->
                                    <div class="flex-1 mb-3 sm:mb-0">
                                        <h3 x-text="item.name" class="text-base sm:text-lg font-medium text-gray-900"></h3>
                                        <div class="mt-1 flex items-center text-xs sm:text-sm text-gray-500">
                                            <span x-text="item.is_common ? 'Common Product' : 'Business Product'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800"></span>
                                            <span class="mx-2">•</span>
                                            <span x-text="'Batch: ' + item.batch_number"></span>
                                        </div>
                                    </div>

                                    <!-- Price & Quantity Controls -->
                                    <div class="flex items-center justify-between sm:space-x-6">
                                        <!-- Enhanced Quantity Controls -->
                                        <div class="flex items-center space-x-2">
                                            <button @click="decrementQuantity(index)"
                                                class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors">
                                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                                </svg>
                                            </button>

                                            <!-- Quantity Input Field -->
                                            <input type="number"
                                                :value="item.quantity"
                                                @input="updateQuantity(index, $event.target.value)"
                                                @blur="updateQuantity(index, $event.target.value)"
                                                step="0.1"
                                                min="0.1"
                                                :max="item.is_common ? 999999 : item.max_stock"
                                                class="w-16 text-center font-medium border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

                                            <button @click="incrementQuantity(index)"
                                                class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors">
                                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Price & Remove -->
                                        <div class="flex items-center space-x-4">
                                            <div class="text-right">
                                                <p class="text-base sm:text-lg font-bold text-gray-900">৳<span x-text="(item.price * item.quantity).toFixed(2)"></span></p>
                                                <p class="text-xs sm:text-sm text-gray-500">৳<span x-text="item.price.toFixed(2)"></span> each</p>
                                            </div>
                                            <button @click="removeItem(index)"
                                                class="text-red-500 hover:text-red-600 transition-colors p-1">
                                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Enhanced Cart Summary -->
                        <div class="py-4 sm:py-6">
                            <div class="flex justify-between items-center text-lg font-bold text-gray-900">
                                <span>Total Amount</span>
                                <span class="text-blue-600">৳<span x-text="getCartTotal().toFixed(2)"></span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Customer Information Form -->
            <div x-show="cartItems.length > 0" class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-4 sm:p-6">
                    <h2 class="text-lg sm:text-xl font-semibold mb-4 sm:mb-6 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Customer Information
                    </h2>
                    <form @submit.prevent="submitOrder" class="space-y-4 sm:space-y-6">
                        <!-- Name Field -->
                        <div class="space-y-1">
                            <label for="customerName" class="block text-sm font-medium text-gray-700">Full Name</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <input type="text" id="customerName" x-model="customerName" required
                                    :disabled="isSubmitting"
                                    class="pl-10 mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                                    placeholder="Enter your full name">
                            </div>
                        </div>

                        <!-- Phone Field -->
                        <div class="space-y-1">
                            <label for="customerPhone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <input type="tel" id="customerPhone" x-model="customerPhone" required
                                    :disabled="isSubmitting"
                                    class="pl-10 mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                                    placeholder="Enter your phone number">
                            </div>
                        </div>

                        <!-- Location Field -->
                        <div class="space-y-1">
                            <label for="location" class="block text-sm font-medium text-gray-700">Delivery Location</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 pt-3 pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <textarea id="location" x-model="location" required rows="3"
                                    :disabled="isSubmitting"
                                    class="pl-10 mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                                    placeholder="Enter your complete delivery address"></textarea>
                            </div>
                        </div>

                        <!-- Order Summary Card -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mt-6">
                            <h3 class="font-medium text-gray-900 mb-2">Order Summary</h3>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Subtotal</span>
                                <span>৳<span x-text="getCartTotal().toFixed(2)"></span></span>
                            </div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Delivery Fee</span>
                                <span>৳0.00</span>
                            </div>
                            <div class="border-t border-gray-200 my-2 pt-2">
                                <div class="flex justify-between font-medium">
                                    <span>Total</span>
                                    <span class="text-blue-600">৳<span x-text="getCartTotal().toFixed(2)"></span></span>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit"
                                :disabled="isSubmitting"
                                :class="{'opacity-50 cursor-not-allowed': isSubmitting, 'hover:bg-blue-700': !isSubmitting}"
                                class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                <svg x-show="isSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="isSubmitting ? 'Processing Order...' : 'Place Order'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Enhanced Footer -->
    <footer class="bg-white border-t mt-auto">
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col items-center justify-center">
                <p class="text-center text-gray-600 mb-2">© {{ date('Y') }} {{ $business->name }}. All rights reserved.</p>
                <div class="flex space-x-4 text-sm text-gray-500">
                    <a href="#" class="hover:text-gray-700 transition-colors">Privacy Policy</a>
                    <span>•</span>
                    <a href="#" class="hover:text-gray-700 transition-colors">Terms of Service</a>
                    <span>•</span>
                    <a href="#" class="hover:text-gray-700 transition-colors">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Enhanced Styles -->
    <style>
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Smooth transitions */
        .transition-colors {
            transition-property: background-color, border-color, color, fill, stroke;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }

        /* Improved focus styles for accessibility */
        input:focus,
        textarea:focus,
        button:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        /* Hide number input arrows on Chrome, Safari, Edge */
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Hide number input arrows on Firefox */
        input[type="number"] {
            -moz-appearance: textfield;
        }
    </style>
</body>

</html>