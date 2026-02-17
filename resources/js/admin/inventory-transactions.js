// Global variables for collection functionality
let currentTransactionId = null;
let categoryData = [];
let maxCollectionAmount = 0;
let isRequestInProgress = false;
let isSubmitting = false;

// Global DOM elements for collection
let collectionModal;
let categoryLoading;
let categoryContent;
let categoryList;
let customerNameElement;
let totalDueElement;
let totalCollectionAmount;
let cancelCollectionBtn;
let saveCollectionBtn;

// Global variables for return functionality
let returnProducts = [];
let returnModal;
let returnProductsLoading;
let returnProductsEmpty;
let returnProductsList;
let returnProductsContainer;
let returnSummary;
let returnSelectedCount;
let returnTotalAmount;
let confirmReturnBtn;
let confirmReturnText;
let returnSpinner;

// Helper function for alerts - used by multiple features
function showAlert(message, type = "info") {
    // Use SweetAlert2 if available
    if (typeof Swal !== "undefined") {
        Swal.fire({
            title: type.charAt(0).toUpperCase() + type.slice(1),
            text: message,
            icon: type,
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
        });
    } else {
        // Fallback to browser alert
        alert(message);
    }
}

// Function to close collection modal
function closeCollectionModal() {
    if (collectionModal) {
        collectionModal.classList.add("hidden");
        collectionModal.style.display = "none";
    }

    // Reset state
    currentTransactionId = null;
    categoryData = [];
    maxCollectionAmount = 0;
    isRequestInProgress = false;
}

// Function to fetch category data
function fetchCategoryData(transactionId) {
    fetch(`/admin/inventory/inventory_transactions/${transactionId}/collect`, {
        method: "GET",
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error("Network response was not ok");
            }
            return response.json();
        })
        .then((data) => {
            isRequestInProgress = false;
            if (data.success) {
                // Store category data
                categoryData = data.categories;

                // Update customer info
                customerNameElement.textContent =
                    data.transaction.customer_name;
                totalDueElement.textContent =
                    "৳" + parseFloat(data.transaction.grand_total).toFixed(2);

                // Render categories
                renderCategories(data.categories);

                // Show content
                categoryLoading.classList.add("hidden");
                categoryContent.classList.remove("hidden");
            } else {
                throw new Error(data.message || "Failed to load categories");
            }
        })
        .catch((error) => {
            isRequestInProgress = false;
            console.error("Error:", error);
            showAlert(
                error.message || "An error occurred while loading categories",
                "error"
            );
            closeCollectionModal();
        });
}

// Function to render categories
// Function to render categories
function renderCategories(categories) {
    categoryList.innerHTML = "";

    if (categories.length === 0) {
        categoryList.innerHTML =
            '<p class="text-gray-500 text-center py-4">No categories found for this transaction.</p>';
        return;
    }

    categories.forEach((category) => {
        // Calculate net total (only considering damages, NOT returns)
        const originalTotal = parseFloat(category.total || 0);
        const damageTotal = parseFloat(category.damage_total || 0);
        const returnTotal = parseFloat(category.return_total || 0);

        // Changed calculation to not subtract returnTotal
        const netTotal = Math.max(0, originalTotal - damageTotal);

        // Only show categories with collectible amounts
        if (netTotal > 0) {
            const categoryItem = document.createElement("div");
            categoryItem.className = "border rounded-lg p-3 bg-white mb-3";

            // Create the HTML with detailed breakdown
            categoryItem.innerHTML = `
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <input type="checkbox" id="category_${
                            category.id
                        }" class="category-checkbox h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            data-id="${category.id}" data-total="${netTotal}">
                        <label for="category_${
                            category.id
                        }" class="ml-2 text-gray-700 font-medium">${
                category.name
            }</label>
                    </div>
                    <span class="text-sm font-semibold text-gray-700">৳${netTotal.toFixed(
                        2
                    )}</span>
                </div>
                
                ${
                    damageTotal > 0 || returnTotal > 0
                        ? `
                <div class="pl-7 mb-2 text-xs">
                    <div class="flex justify-between text-gray-600">
                        <span>Original Total:</span>
                        <span>৳${originalTotal.toFixed(2)}</span>
                    </div>
                    ${
                        damageTotal > 0
                            ? `
                    <div class="flex justify-between text-red-600">
                        <span>Damage Total:</span>
                        <span>-৳${damageTotal.toFixed(2)}</span>
                    </div>`
                            : ""
                    }
                    <div class="flex justify-between font-medium text-green-600 border-t border-gray-200 mt-1 pt-1">
                        <span>Net Collectible:</span>
                        <span>৳${netTotal.toFixed(2)}</span>
                    </div>
                </div>`
                        : ""
                }
                
                <div class="pl-7">
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">৳</span>
                        </div>
                        <input type="number" class="category-amount pl-7 w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 text-sm py-1"
                            id="amount_${category.id}" value="0.00"
                            min="0.00" max="${netTotal}" step="0.01" disabled>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        Max: ৳${netTotal.toFixed(2)} | ${
                category.products ? category.products.length : 0
            } product(s)
                    </div>
                </div>
            `;

            categoryList.appendChild(categoryItem);
        }
    });

    // If no categories with collectible amounts, show message
    if (categoryList.children.length === 0) {
        categoryList.innerHTML =
            '<p class="text-gray-500 text-center py-4">No collectible amounts found for this transaction.</p>';
    }

    // Add event listeners to checkboxes
    document.querySelectorAll(".category-checkbox").forEach((checkbox) => {
        checkbox.addEventListener("change", function () {
            const categoryId = this.getAttribute("data-id");
            const amountInput = document.getElementById(`amount_${categoryId}`);

            if (this.checked) {
                amountInput.disabled = false;
                // Keep default value as 0.00 - user will input manually
                amountInput.value = "0.00";
                // Focus on the input field for better UX
                amountInput.focus();
                amountInput.select();
            } else {
                amountInput.disabled = true;
                amountInput.value = "0.00";
            }

            updateTotalAmount();
        });
    });

    // Add event listeners to amount inputs
    document.querySelectorAll(".category-amount").forEach((input) => {
        input.addEventListener("change", function () {
            const max = parseFloat(this.getAttribute("max"));
            let value = parseFloat(this.value);

            if (isNaN(value) || value < 0) {
                value = 0;
            } else if (value > max) {
                value = max;
                showAlert(`Amount cannot exceed ৳${max.toFixed(2)}`, "warning");
            }

            this.value = value.toFixed(2);
            updateTotalAmount();
        });

        // Add real-time input validation
        input.addEventListener("input", function () {
            const max = parseFloat(this.getAttribute("max"));
            let value = parseFloat(this.value);

            if (!isNaN(value) && value > max) {
                this.style.borderColor = "#ef4444";
                this.style.backgroundColor = "#fef2f2";
            } else {
                this.style.borderColor = "";
                this.style.backgroundColor = "";
            }
        });
    });
}

// Function to update total amount based on selected categories
function updateTotalAmount() {
    let total = 0;
    document
        .querySelectorAll(".category-checkbox:checked")
        .forEach((checkbox) => {
            const categoryId = checkbox.getAttribute("data-id");
            const amountInput = document.getElementById(`amount_${categoryId}`);
            const amount = parseFloat(amountInput.value || 0);
            total += amount;
        });

    // Update total collection amount
    if (totalCollectionAmount) {
        totalCollectionAmount.value = total.toFixed(2);
    }
}

// Initialize collection functionality
function initializeCollectionFunctionality() {
    // Get DOM elements
    collectionModal = document.getElementById("collectionModal");
    categoryLoading = document.getElementById("categoryLoading");
    categoryContent = document.getElementById("categoryContent");
    categoryList = document.getElementById("categoryList");
    customerNameElement = document.getElementById("customerName");
    totalDueElement = document.getElementById("totalDue");
    totalCollectionAmount = document.getElementById("totalCollectionAmount");
    cancelCollectionBtn = document.getElementById("cancelCollectionBtn");
    saveCollectionBtn = document.getElementById("saveCollectionBtn");

    // Initialize collection buttons
    document
        .querySelectorAll(".collection-btn:not([data-initialized])")
        .forEach((btn) => {
            if (btn.getAttribute("disabled") === null) {
                // Only add listeners to enabled buttons
                btn.addEventListener("click", function (e) {
                    e.preventDefault();

                    // Prevent multiple requests
                    if (isRequestInProgress) return;
                    isRequestInProgress = true;

                    // Get transaction ID and amount
                    currentTransactionId = this.getAttribute("data-id");
                    maxCollectionAmount = parseFloat(
                        this.getAttribute("data-amount") || 0
                    );

                    // Additional check to prevent collection if amount is zero or negative
                    if (maxCollectionAmount <= 0) {
                        showAlert(
                            "No amount to collect for this transaction.",
                            "warning"
                        );
                        isRequestInProgress = false;
                        return;
                    }

                    // Reset form
                    if (totalCollectionAmount)
                        totalCollectionAmount.value =
                            maxCollectionAmount.toFixed(2);

                    // Show modal with loading state
                    if (collectionModal) {
                        collectionModal.classList.remove("hidden");
                        collectionModal.style.display = "flex";
                        categoryLoading.classList.remove("hidden");
                        categoryContent.classList.add("hidden");
                    }

                    // Fetch category data
                    fetchCategoryData(currentTransactionId);
                });
                btn.setAttribute("data-initialized", "true");
            }
        });

    // Handle save collection button
    // Handle save collection button
    if (saveCollectionBtn) {
        saveCollectionBtn.addEventListener("click", function () {
            if (!currentTransactionId || isSubmitting) return;

            isSubmitting = true;

            const selectedCategories = [];
            let hasSelection = false;

            document
                .querySelectorAll(".category-checkbox:checked")
                .forEach((checkbox) => {
                    hasSelection = true;
                    const categoryId = checkbox.getAttribute("data-id");
                    const amount = parseFloat(
                        document.getElementById(`amount_${categoryId}`).value
                    );

                    selectedCategories.push({
                        category_id: parseInt(categoryId),
                        amount: amount,
                    });
                });

            if (!hasSelection) {
                Swal.fire({
                    title: "No Categories Selected",
                    text: "Please select at least one category to collect payment from.",
                    icon: "warning",
                    confirmButtonText: "OK",
                    confirmButtonColor: "#f59e0b",
                });
                isSubmitting = false;
                return;
            }

            const totalAmount = parseFloat(totalCollectionAmount.value);

            if (isNaN(totalAmount) || totalAmount <= 0) {
                Swal.fire({
                    title: "Invalid Amount",
                    text: "Please enter a valid total amount greater than 0.",
                    icon: "warning",
                    confirmButtonText: "OK",
                    confirmButtonColor: "#f59e0b",
                });
                isSubmitting = false;
                return;
            }

            if (totalAmount > maxCollectionAmount) {
                Swal.fire({
                    title: "Amount Exceeded",
                    text: `Total amount cannot exceed ৳${maxCollectionAmount.toFixed(
                        2
                    )}`,
                    icon: "warning",
                    confirmButtonText: "OK",
                    confirmButtonColor: "#f59e0b",
                });
                isSubmitting = false;
                return;
            }

            // Show loading state
            this.disabled = true;
            this.classList.add("cursor-not-allowed", "opacity-75");
            this.innerHTML =
                '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';

            // Disable all checkboxes and amount inputs during submission
            document
                .querySelectorAll(".category-checkbox, .category-amount")
                .forEach((el) => {
                    el.disabled = true;
                });

            // Disable the total amount input
            if (totalCollectionAmount) {
                totalCollectionAmount.disabled = true;
            }

            // Send collection request
            fetch(
                `/admin/inventory/inventory_transactions/${currentTransactionId}/collect`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        categories: selectedCategories,
                        total_amount: totalAmount,
                    }),
                }
            )
                .then((response) => {
                    if (!response.ok) {
                        return response
                            .json()
                            .then((data) => Promise.reject(data));
                    }
                    return response.json();
                })
                .then((data) => {
                    // Reset button state
                    isSubmitting = false;
                    this.disabled = false;
                    this.classList.remove("cursor-not-allowed", "opacity-75");
                    this.innerHTML = "Save Collection";

                    // Close modal
                    closeCollectionModal();

                    // Show detailed success message with SweetAlert
                    Swal.fire({
                        title: "Collection Successful!",
                        html: `
                        <div class="text-left">
                            <p class="mb-2"><strong>Amount Collected:</strong> ৳${totalAmount.toFixed(
                                2
                            )}</p>
                            <p class="mb-2"><strong>Categories:</strong> ${
                                selectedCategories.length
                            }</p>
                            <p class="text-green-600 font-medium">${
                                data.message ||
                                "Payment collected successfully!"
                            }</p>
                        </div>
                    `,
                        icon: "success",
                        confirmButtonText: "OK",
                        confirmButtonColor: "#10b981",
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                    }).then(() => {
                        // Reload page after user acknowledges
                        window.location.reload();
                    });
                })
                .catch((error) => {
                    // Reset button state
                    isSubmitting = false;
                    this.disabled = false;
                    this.classList.remove("cursor-not-allowed", "opacity-75");
                    this.innerHTML = "Save Collection";

                    // Re-enable all checkboxes and amount inputs
                    document
                        .querySelectorAll(
                            ".category-checkbox, .category-amount"
                        )
                        .forEach((el) => {
                            const categoryId = el.getAttribute("data-id");
                            if (categoryId) {
                                const checkbox = document.querySelector(
                                    `.category-checkbox[data-id="${categoryId}"]`
                                );
                                if (checkbox && checkbox.checked) {
                                    el.disabled = false;
                                } else if (
                                    el.classList.contains("category-checkbox")
                                ) {
                                    el.disabled = false;
                                }
                            } else {
                                el.disabled = false;
                            }
                        });

                    // Re-enable the total amount input
                    if (totalCollectionAmount) {
                        totalCollectionAmount.disabled = false;
                    }

                    // Show detailed error message with SweetAlert
                    Swal.fire({
                        title: "Collection Failed!",
                        html: `
                        <div class="text-left">
                            <p class="mb-2"><strong>Transaction ID:</strong> ${currentTransactionId}</p>
                            <p class="mb-2"><strong>Attempted Amount:</strong> ৳${totalAmount.toFixed(
                                2
                            )}</p>
                            <p class="text-red-600 font-medium">${
                                error.message ||
                                "Failed to process collection. Please try again."
                            }</p>
                            ${
                                error.errors
                                    ? `
                                <div class="mt-3">
                                    <p class="font-medium text-gray-700">Details:</p>
                                    <ul class="list-disc list-inside text-sm text-gray-600">
                                        ${Object.values(error.errors)
                                            .flat()
                                            .map((err) => `<li>${err}</li>`)
                                            .join("")}
                                    </ul>
                                </div>
                            `
                                    : ""
                            }
                        </div>
                    `,
                        icon: "error",
                        confirmButtonText: "Try Again",
                        confirmButtonColor: "#ef4444",
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                    });

                    console.error("Collection error:", error);
                });
        });
    }

    // Handle cancel buttons
    if (cancelCollectionBtn) {
        cancelCollectionBtn.addEventListener("click", closeCollectionModal);
    }

    const cancelCollectionX = document.getElementById("cancelCollection");
    if (cancelCollectionX) {
        cancelCollectionX.addEventListener("click", closeCollectionModal);
    }
}

// Make showNoProductsWarning globally accessible
window.showNoProductsWarning = function (userType) {
    let message = "Please add products first before creating a transaction";
    if (userType === "staff") {
        message =
            "No products found in your assigned categories. Please contact an administrator to assign product categories.";
    }
    Swal.fire({
        title: "No Products Available",
        text: message,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText:
            userType === "staff" ? "Contact Admin" : "Add Products",
        cancelButtonText: "Cancel",
    }).then((result) => {
        if (result.isConfirmed) {
            if (userType === "staff") {
                // Optionally, you could open an email or communication method
                window.location.href =
                    "mailto:admin@yourcompany.com?subject=Product Category Access Request";
            } else {
                // Use direct URL instead of route helper
                window.location.href = "/admin/inventory/products/create";
            }
        }
    });
};

// Function to close return modal
function closeReturnModal() {
    if (returnModal) {
        returnModal.classList.add("hidden");
        returnModal.style.display = "none";
    }
    // Reset state
    currentTransactionId = null;
    returnProducts = [];
    updateReturnSummary();
}

// Function to handle product return
function handleProductReturn(transactionId) {
    // Show loading state
    returnProductsLoading.classList.remove("hidden");
    returnProductsEmpty.classList.add("hidden");
    returnProductsList.innerHTML = "";
    returnSummary.classList.add("hidden");
    returnProductsContainer.classList.add("hidden");

    // Show modal
    returnModal.classList.remove("hidden");
    returnModal.style.display = "flex";

    // Fetch products for the transaction
    fetch(`/admin/inventory/inventory_transactions/${transactionId}/products`)
        .then((response) => {
            if (!response.ok) {
                throw new Error("Network response was not ok");
            }
            return response.json();
        })
        .then((data) => {
            // Hide loading indicator
            returnProductsLoading.classList.add("hidden");

            console.log("Received products data:", data); // Debug log

            if (data.success && data.products && data.products.length > 0) {
                returnProducts = data.products;
                renderReturnProducts(data.products);
                returnProductsContainer.classList.remove("hidden");
                returnSummary.classList.remove("hidden");
            } else {
                console.log("No products available for return"); // Debug log
                returnProductsEmpty.classList.remove("hidden");
                // Update the empty state message
                const emptyStateDiv = returnProductsEmpty.querySelector("p");
                if (emptyStateDiv) {
                    emptyStateDiv.textContent =
                        data.message ||
                        "All products have already been returned.";
                }
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            returnProductsLoading.classList.add("hidden");
            returnProductsEmpty.classList.remove("hidden");

            // Update error message
            const emptyStateDiv = returnProductsEmpty.querySelector("p");
            if (emptyStateDiv) {
                emptyStateDiv.textContent =
                    "Error loading products. Please try again.";
            }
        });
}

// Function to render return products
function renderReturnProducts(products) {
    // Show the products container
    returnProductsContainer.classList.remove("hidden");

    // Generate HTML for each product
    returnProductsList.innerHTML = products
        .map(
            (product) => `
        <div class="product-item p-4 border-b border-gray-200" 
             data-product-id="${product.id}" 
             data-batch-id="${product.batch_id}"
             data-max-quantity="${product.quantity}">
            <div class="flex items-start">
                <!-- Checkbox for selection -->
                <div class="mr-3 pt-1">
                    <input type="checkbox" class="return-select h-5 w-5 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                </div>
                
                <!-- Product details -->
                <div class="flex-grow">
                    <div class="font-medium text-gray-900">${product.name}</div>
                    <div class="text-xs text-gray-500 mt-0.5">Batch: ${
                        product.batch_number || "N/A"
                    }</div>
                    
                    <div class="flex flex-wrap items-center mt-2">
                        <!-- Available quantity -->
                        <div class="bg-blue-100 rounded-full px-2 py-0.5 text-xs text-blue-600 mr-2">
                            Available: ${product.quantity}
                        </div>
                        
                        <!-- Unit price -->
                        <div class="bg-gray-100 rounded-full px-2 py-0.5 text-xs text-gray-600">
                            Price: ৳${parseFloat(product.unit_price).toFixed(2)}
                        </div>
                    </div>
                    
                    <!-- Quantity selector -->
                    <div class="mt-3 flex items-center">
                        <label class="text-xs text-gray-500 mr-2">Return Qty:</label>
                        <div class="flex items-center border rounded-md overflow-hidden">
                            <button type="button" class="qty-decrement px-2 py-1 bg-gray-100 text-gray-600 hover:bg-gray-200 focus:outline-none">
                                <i class="fas fa-minus text-xs"></i>
                            </button>
                            <input type="number" 
                                class="return-qty w-20 text-center border-x border-gray-200 py-1 text-sm" 
                                min="0.01" 
                                max="${product.quantity}" 
                                value="1"
                                step="0.01">
                            <button type="button" class="qty-increment px-2 py-1 bg-gray-100 text-gray-600 hover:bg-gray-200 focus:outline-none">
                                <i class="fas fa-plus text-xs"></i>
                            </button>
                        </div>
                        <div class="ml-2 text-xs text-gray-500">
                            Max: ${product.quantity}
                        </div>
                    </div>
                </div>
                
                <!-- Total amount (updated dynamically) -->
                <div class="ml-2 text-right">
                    <div class="text-xs text-gray-500">Total</div>
                    <div class="product-total font-medium text-amber-600">৳${parseFloat(
                        product.unit_price
                    ).toFixed(2)}</div>
                </div>
            </div>
        </div>
    `
        )
        .join("");

    // Add event listeners for quantity controls with validation
    returnProductsList.querySelectorAll(".qty-decrement").forEach((btn) => {
        btn.addEventListener("click", function () {
            const productItem = this.closest(".product-item");
            const input = productItem.querySelector(".return-qty");
            const currentValue = parseFloat(input.value);
            if (currentValue > 0.01) {
                const newValue = Math.max(0.01, currentValue - 1).toFixed(2);
                input.value = newValue;
                updateProductTotal(productItem, newValue);
                updateReturnSummary();
            }
        });
    });

    returnProductsList.querySelectorAll(".qty-increment").forEach((btn) => {
        btn.addEventListener("click", function () {
            const productItem = this.closest(".product-item");
            const input = productItem.querySelector(".return-qty");
            const currentValue = parseFloat(input.value);
            const maxValue = parseFloat(
                productItem.getAttribute("data-max-quantity")
            );

            if (currentValue < maxValue) {
                const newValue = Math.min(maxValue, currentValue + 1).toFixed(
                    2
                );
                input.value = newValue;
                updateProductTotal(productItem, newValue);
                updateReturnSummary();
            } else {
                // Show SweetAlert warning
                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        title: "Maximum Quantity Reached",
                        text: `You cannot return more than ${maxValue} units of this product.`,
                        icon: "warning",
                        confirmButtonText: "OK",
                        confirmButtonColor: "#f59e0b",
                    });
                } else {
                    alert(
                        `You cannot return more than ${maxValue} units of this product.`
                    );
                }
            }
        });
    });

    // Add event listeners for quantity inputs with validation
    returnProductsList.querySelectorAll(".return-qty").forEach((input) => {
        input.addEventListener("change", function () {
            const productItem = this.closest(".product-item");
            const maxValue = parseFloat(
                productItem.getAttribute("data-max-quantity")
            );
            let value = parseFloat(this.value);

            if (isNaN(value) || value < 0.01) {
                value = 0.01;
                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        title: "Invalid Quantity",
                        text: "Quantity must be at least 0.01",
                        icon: "warning",
                        confirmButtonText: "OK",
                        confirmButtonColor: "#f59e0b",
                    });
                } else {
                    alert("Quantity must be at least 0.01");
                }
            } else if (value > maxValue) {
                value = maxValue;
                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        title: "Quantity Too High",
                        text: `You cannot return more than ${maxValue} units of this product. Available quantity has been set.`,
                        icon: "warning",
                        confirmButtonText: "OK",
                        confirmButtonColor: "#f59e0b",
                    });
                } else {
                    alert(
                        `You cannot return more than ${maxValue} units of this product.`
                    );
                }
            }

            this.value = value.toFixed(2);
            updateProductTotal(productItem, value);
            updateReturnSummary();
        });

        // Add real-time validation on input
        input.addEventListener("input", function () {
            const productItem = this.closest(".product-item");
            const maxValue = parseFloat(
                productItem.getAttribute("data-max-quantity")
            );
            let value = parseFloat(this.value);

            if (!isNaN(value) && value > maxValue) {
                // Visual feedback for invalid input
                this.style.borderColor = "#ef4444";
                this.style.backgroundColor = "#fef2f2";
            } else {
                // Reset to normal styling
                this.style.borderColor = "";
                this.style.backgroundColor = "";
            }
        });
    });

    // Add event listeners for checkboxes
    returnProductsList
        .querySelectorAll(".return-select")
        .forEach((checkbox) => {
            checkbox.addEventListener("change", function () {
                const productItem = this.closest(".product-item");

                // Toggle active state visually
                if (this.checked) {
                    productItem.classList.add("bg-amber-50");
                } else {
                    productItem.classList.remove("bg-amber-50");
                }

                updateReturnSummary();
            });
        });

    // Initialize summary
    updateReturnSummary();
}

// Helper function to update product total
function updateProductTotal(productItem, quantity) {
    const productId = productItem.getAttribute("data-product-id");
    const product = returnProducts.find((p) => p.id == productId);

    if (product) {
        const total = quantity * parseFloat(product.unit_price);
        productItem.querySelector(".product-total").textContent =
            "৳" + total.toFixed(2);
    }
}

// Function to update return summary
function updateReturnSummary() {
    if (!returnSelectedCount || !returnTotalAmount) return;

    const selectedItems = document.querySelectorAll(".return-select:checked");
    const selectedCount = selectedItems.length;
    let totalAmount = 0;

    selectedItems.forEach((checkbox) => {
        const productItem = checkbox.closest(".product-item");
        const productId = productItem.getAttribute("data-product-id");
        const quantity = parseFloat(
            productItem.querySelector(".return-qty").value
        );
        const product = returnProducts.find((p) => p.id == productId);

        if (product) {
            totalAmount += quantity * parseFloat(product.unit_price);
        }
    });

    returnSelectedCount.textContent = selectedCount;
    returnTotalAmount.textContent = "৳" + totalAmount.toFixed(2);

    // Enable/disable confirm button based on selection
    if (confirmReturnBtn) {
        if (selectedCount > 0 && !isSubmitting) {
            confirmReturnBtn.disabled = false;

            // First, remove all possible state classes
            confirmReturnBtn.classList.remove(
                "bg-gray-400",
                "hover:bg-gray-500",
                "cursor-not-allowed",
                "bg-amber-600",
                "hover:bg-amber-700"
            );

            // Then add the enabled state classes
            confirmReturnBtn.classList.add(
                "bg-amber-600",
                "hover:bg-amber-700"
            );
        } else {
            confirmReturnBtn.disabled = true;

            // First, remove all possible state classes
            confirmReturnBtn.classList.remove(
                "bg-amber-600",
                "hover:bg-amber-700",
                "bg-gray-400",
                "hover:bg-gray-500"
            );

            // Then add the disabled state classes
            confirmReturnBtn.classList.add(
                "bg-gray-400",
                "hover:bg-gray-500",
                "cursor-not-allowed"
            );
        }
    }
}

// Initialize return products functionality
function initializeReturnProductsFunctionality() {
    // Get DOM elements
    returnModal = document.getElementById("returnModal");
    returnProductsLoading = document.getElementById("returnProductsLoading");
    returnProductsEmpty = document.getElementById("returnProductsEmpty");
    returnProductsList = document.getElementById("returnProductsList");
    returnProductsContainer = document.getElementById(
        "returnProductsContainer"
    );
    returnSummary = document.getElementById("returnSummary");
    returnSelectedCount = document.getElementById("returnSelectedCount");
    returnTotalAmount = document.getElementById("returnTotalAmount");
    confirmReturnBtn = document.getElementById("confirmReturn");
    confirmReturnText = document.getElementById("confirmReturnText");
    returnSpinner = document.getElementById("returnSpinner");

    // Store transaction ID when opening return modal
    document
        .querySelectorAll(".return-btn:not([data-initialized])")
        .forEach((btn) => {
            btn.addEventListener("click", function (e) {
                e.preventDefault();

                // Check if button is disabled
                if (this.hasAttribute("disabled")) {
                    showAlert(
                        "Returns are not available for fully returned transactions",
                        "info"
                    );
                    return;
                }

                currentTransactionId = this.getAttribute("data-id");
                handleProductReturn(currentTransactionId);
            });
            btn.setAttribute("data-initialized", "true");
        });

    // Close return modal
    const closeReturnModalBtn = document.getElementById("closeReturnModal");
    if (
        closeReturnModalBtn &&
        !closeReturnModalBtn.hasAttribute("data-initialized")
    ) {
        closeReturnModalBtn.addEventListener("click", closeReturnModal);
        closeReturnModalBtn.setAttribute("data-initialized", "true");
    }

    const cancelReturnBtn = document.getElementById("cancelReturn");
    if (cancelReturnBtn && !cancelReturnBtn.hasAttribute("data-initialized")) {
        cancelReturnBtn.addEventListener("click", closeReturnModal);
        cancelReturnBtn.setAttribute("data-initialized", "true");
    }

    // Handle confirm return button
    if (
        confirmReturnBtn &&
        !confirmReturnBtn.hasAttribute("data-initialized")
    ) {
        confirmReturnBtn.addEventListener("click", function () {
            if (!currentTransactionId || isSubmitting) return;

            const selectedProducts = [];
            let hasValidationError = false;

            document
                .querySelectorAll(".return-select:checked")
                .forEach((checkbox) => {
                    const productItem = checkbox.closest(".product-item");
                    const quantity = parseFloat(
                        productItem.querySelector(".return-qty").value
                    );
                    const maxQuantity = parseFloat(
                        productItem.getAttribute("data-max-quantity")
                    );
                    const productName = returnProducts.find(
                        (p) =>
                            p.id == productItem.getAttribute("data-product-id")
                    ).name;

                    // Validate quantity
                    if (quantity > maxQuantity) {
                        hasValidationError = true;
                        Swal.fire({
                            title: "Invalid Quantity",
                            text: `Cannot return ${quantity} units of "${productName}". Maximum available: ${maxQuantity}`,
                            icon: "error",
                            confirmButtonText: "OK",
                            confirmButtonColor: "#ef4444",
                        });
                        return;
                    }

                    selectedProducts.push({
                        product_id: parseInt(
                            productItem.getAttribute("data-product-id")
                        ),
                        batch_id: parseInt(
                            productItem.getAttribute("data-batch-id")
                        ),
                        quantity: quantity,
                        unit_price: returnProducts.find(
                            (p) =>
                                p.id ==
                                productItem.getAttribute("data-product-id")
                        ).unit_price,
                    });
                });

            if (hasValidationError) {
                return;
            }

            if (selectedProducts.length === 0) {
                Swal.fire({
                    title: "No Products Selected",
                    text: "Please select at least one product to return",
                    icon: "warning",
                    confirmButtonText: "OK",
                    confirmButtonColor: "#f59e0b",
                });
                return;
            }

            // Set submitting state to true
            isSubmitting = true;

            // Show loading state and disable button
            this.disabled = true;
            this.classList.remove("bg-amber-600", "hover:bg-amber-700");
            this.classList.add(
                "bg-gray-400",
                "hover:bg-gray-500",
                "cursor-not-allowed"
            );
            confirmReturnText.textContent = "Processing...";
            returnSpinner.classList.remove("hidden");

            // Disable all checkboxes and quantity inputs during submission
            document
                .querySelectorAll(".return-select, .return-qty")
                .forEach((el) => {
                    el.disabled = true;
                });

            // Send return request
            fetch(
                `/admin/inventory/inventory_transactions/${currentTransactionId}/return`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        products: selectedProducts,
                    }),
                }
            )
                .then((response) => {
                    if (!response.ok) {
                        return response
                            .json()
                            .then((data) => Promise.reject(data));
                    }
                    return response.json();
                })
                .then((data) => {
                    // Reset button state
                    isSubmitting = false;
                    this.disabled = false;
                    confirmReturnText.textContent = "Confirm Return";
                    returnSpinner.classList.add("hidden");

                    // Re-enable all checkboxes and quantity inputs
                    document
                        .querySelectorAll(".return-select, .return-qty")
                        .forEach((el) => {
                            el.disabled = false;
                        });

                    // Update button styling based on selection
                    updateReturnSummary();

                    // Close modal
                    closeReturnModal();

                    // Show success message
                    Swal.fire({
                        title: "Success!",
                        text: data.message || "Products returned successfully!",
                        icon: "success",
                        confirmButtonText: "OK",
                        confirmButtonColor: "#10b981",
                    }).then(() => {
                        // Reload page after success
                        window.location.reload();
                    });
                })
                .catch((error) => {
                    // Reset button state
                    isSubmitting = false;
                    this.disabled = false;
                    confirmReturnText.textContent = "Confirm Return";
                    returnSpinner.classList.add("hidden");

                    // Re-enable all checkboxes and quantity inputs
                    document
                        .querySelectorAll(".return-select, .return-qty")
                        .forEach((el) => {
                            el.disabled = false;
                        });

                    // Update button styling based on selection
                    updateReturnSummary();

                    // Show error message
                    Swal.fire({
                        title: "Error!",
                        text:
                            error.message ||
                            "Failed to process return. Please try again.",
                        icon: "error",
                        confirmButtonText: "OK",
                        confirmButtonColor: "#ef4444",
                    });
                    console.error("Return error:", error);
                });
        });

        // Mark as initialized to prevent duplicate event listeners
        confirmReturnBtn.setAttribute("data-initialized", "true");
    }
}

// Initialize delete transaction functionality
function initializeDeleteFunctionality() {
    // Handle delete transaction buttons
    document
        .querySelectorAll(".delete-btn:not([data-initialized])")
        .forEach((btn) => {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                const transactionId = this.getAttribute("data-id");

                Swal.fire({
                    title: "Are you sure?",
                    text: "This will delete the transaction and all related records. This action cannot be undone!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonText: "Cancel",
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state
                        Swal.fire({
                            title: "Deleting...",
                            text: "Please wait while we delete the transaction",
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            },
                        });

                        // Send delete request
                        fetch(
                            `/admin/inventory/inventory_transactions/${transactionId}`,
                            {
                                method: "DELETE",
                                headers: {
                                    "X-CSRF-TOKEN": document
                                        .querySelector(
                                            'meta[name="csrf-token"]'
                                        )
                                        .getAttribute("content"),
                                    Accept: "application/json",
                                    "Content-Type": "application/json",
                                },
                            }
                        )
                            .then((response) => response.json())
                            .then((data) => {
                                if (data.success) {
                                    Swal.fire({
                                        title: "Deleted!",
                                        text:
                                            data.message ||
                                            "Transaction has been deleted successfully.",
                                        icon: "success",
                                    }).then(() => {
                                        // Reload the page to reflect changes
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: "Error!",
                                        text:
                                            data.message ||
                                            "Failed to delete transaction.",
                                        icon: "error",
                                    });
                                }
                            })
                            .catch((error) => {
                                console.error("Delete error:", error);
                                Swal.fire({
                                    title: "Error!",
                                    text: "An unexpected error occurred. Please try again.",
                                    icon: "error",
                                });
                            });
                    }
                });
            });
            btn.setAttribute("data-initialized", "true");
        });
}

// Main initialization function
function initializeEventListeners() {
    // Initialize collection functionality
    initializeCollectionFunctionality();

    // Initialize return products functionality
    initializeReturnProductsFunctionality();

    // Initialize delete functionality
    initializeDeleteFunctionality();
}

// Make the initialization function globally accessible
window.initializeEventListeners = initializeEventListeners;

// Initialize everything when the DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
    // Initialize all event listeners for interactive elements
    initializeEventListeners();
});
