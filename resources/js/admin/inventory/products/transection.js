document.addEventListener("DOMContentLoaded", function () {
    const isPurchaseForm = document.getElementById("purchaseForm") !== null;
    const isSaleForm = document.getElementById("saleForm") !== null;
    const submitButton = document.querySelector("#submitTransaction");
    // Get the disable_underprice setting from the data attribute
    const containerEl = document.querySelector(".container-fluid");
    const disableUnderpriceAttr = containerEl.getAttribute(
        "data-disable-underprice"
    );
    const disableUnderprice = disableUnderpriceAttr === "true";
    console.log("Submit button found:", submitButton);
    console.log("Form type detection:", {
        isPurchaseForm,
        isSaleForm,
        rawAttribute: disableUnderpriceAttr,
        parsedValue: disableUnderprice,
        containerElement: containerEl,
    });

    if (submitButton) {
        submitButton.addEventListener("click", function (e) {
            e.preventDefault();
            this.disabled = true;
            this.classList.remove(
                "from-blue-500",
                "to-blue-600",
                "hover:from-blue-600",
                "hover:to-blue-700"
            );
            this.classList.add("from-red-500", "to-red-600");

            if (isPurchaseForm) {
                console.log("Submitting purchase form");
                submitPurchaseTransaction();
            } else if (isSaleForm) {
                console.log("Submitting sales form");
                submitSalesTransaction();
            }
        });
    } else {
        console.error("Submit button not found in DOM");
    }
    if (!isPurchaseForm) {
        console.log(
            "Sales form detected - ensuring damage buttons are visible"
        );
        document.querySelectorAll(".damaged-btn").forEach((button) => {
            button.style.display = "block";
        });
    }

    if (isPurchaseForm) {
        // For purchase form
        function addProductToTransaction(productData) {
            addPurchaseProductToTransaction(productData);
        }
    } else {
        // For sale form
        function addProductToTransaction(productData) {
            if (productData.type === "damaged") {
                addDamagedProductToTransaction(productData);
            } else {
                addRegularProductToTransaction(productData);
            }
        }
    }
});

const TransactionManager = {
    removeLine: function (lineItem) {
        lineItem.remove();
        calculateTotal();
        calculateDamageTotal();
        updateTotalPriceDifference();
    },
};

// Function to add regular product into the transections form

function isDuplicateProduct(productId, type = "regular") {
    const selector =
        type === "damaged"
            ? "#productLinesContainer .line-item.bg-red-50\\/30"
            : "#productLinesContainer .line-item:not(.bg-red-50\\/30)";

    const productLines = document.querySelectorAll(selector);

    for (let i = 0; i < productLines.length; i++) {
        const line = productLines[i];
        const lineProductId = line.dataset.productId;

        if (lineProductId === productId) {
            console.log(
                `Found duplicate ${type} product:`,
                productId,
                "in line:",
                line
            );
            return true;
        }
    }

    return false;
}

function addProductToTransaction(productData) {
    console.log("Adding product to transaction:", productData);
    console.log("Current DOM state before adding:");
    console.log(
        "- Product line container:",
        document.querySelector("#productLinesContainer")
    );
    console.log(
        "- Existing line items:",
        document.querySelectorAll("#productLinesContainer .line-item")
    );

    // Check if product already exists in the transaction
    if (isDuplicateProduct(productData.productId)) {
        Swal.fire({
            icon: "error",
            title: "Duplicate Product",
            text: "This product is already added to the transaction. Please modify the existing line instead of adding it again.",
        });
        return;
    }

    const template = document.getElementById("productLineTemplate");
    const newLine = template.content
        .cloneNode(true)
        .querySelector(".line-item");
    console.log("Created new line element:", newLine);

    // Log each value being set
    console.log("Setting product ID:", productData.productId);
    console.log("Setting trade price:", productData.tradePrice);

    newLine.dataset.productId = productData.productId;
    newLine.dataset.tradePrice = productData.tradePrice;

    const productSelector = newLine.querySelector(".product-selector");
    $(productSelector).val(productData.productId).trigger("change");

    const quantityInput = newLine.querySelector(".quantity");
    quantityInput.value = productData.quantity;
    quantityInput.addEventListener("input", () =>
        calculatePriceDifference(quantityInput, false)
    );

    // Get the disable_underprice setting
    const disableUnderprice =
        document.querySelector(".container-fluid").dataset.disableUnderprice ===
        "true";

    // Set the initial price, ensuring it's not below trade price if underprice is disabled
    const unitPriceInput = newLine.querySelector(".unit-price");
    if (
        disableUnderprice &&
        parseFloat(productData.price) < parseFloat(productData.tradePrice)
    ) {
        // If underprice is disabled and initial price is below trade price, set to trade price
        unitPriceInput.value = productData.tradePrice;
    } else {
        // Otherwise use the provided price
        unitPriceInput.value = productData.price;
    }

    // Add event listeners for price changes - these will show warnings
    unitPriceInput.addEventListener("input", () =>
        calculatePriceDifference(unitPriceInput, true)
    );
    unitPriceInput.addEventListener("change", () =>
        calculatePriceDifference(unitPriceInput, true)
    );

    const tradePriceInput = newLine.querySelector(".trade-price");
    tradePriceInput.value = productData.tradePrice;
    const tradePriceDisplay = newLine.querySelector(".trade-price-display");
    tradePriceDisplay.textContent = productData.tradePrice;

    // Calculate price difference initially WITHOUT showing warning
    calculatePriceDifference(unitPriceInput, false);

    document.querySelector("#productLinesContainer").appendChild(newLine);
    $(newLine).find(".select2").select2();

    // Log line items after addition
    console.log(
        "Current line items:",
        document.querySelectorAll("#productLinesContainer .line-item")
    );

    calculateTotal();
}

// Function to handle mobile toggle button click
document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("toggleFormBtn");
    const formSection = document.getElementById("transactionForm");
    const toggleIcon = document.querySelector(".toggle-icon");

    toggleBtn.addEventListener("click", function () {
        formSection.classList.toggle("translate-x-full");
        toggleIcon.classList.toggle("rotate-180");
    });
});

// Function to handle Damage products entry
function addDamagedProductToTransaction(productData) {
    console.log("Adding damaged product to transaction:", productData);

    // Check if product already exists in the damaged products
    if (isDuplicateProduct(productData.productId, "damaged")) {
        Swal.fire({
            icon: "error",
            title: "Duplicate Damaged Product",
            text: "This product is already added to the damaged products list. Please modify the existing line instead of adding it again.",
        });
        return;
    }

    const template = document.getElementById("damagedProductLineTemplate");
    const newLine = template.content
        .cloneNode(true)
        .querySelector(".line-item");

    // Make sure to set the product ID in the dataset
    newLine.dataset.productId = productData.productId;

    const productSelector = newLine.querySelector(".product-selector");
    $(productSelector).val(productData.productId).trigger("change");

    const quantityInput = newLine.querySelector(".quantity");
    quantityInput.value = productData.quantity;
    quantityInput.addEventListener("input", calculateDamageTotal);

    const unitPriceInput = newLine.querySelector(".unit-price");
    unitPriceInput.value = productData.price;
    unitPriceInput.addEventListener("input", calculateDamageTotal);

    document.querySelector("#productLinesContainer").appendChild(newLine);
    $(newLine).find(".select2").select2();

    calculateDamageTotal();

    console.log("Damaged product added:", productData.productId);
    console.log(
        "Current damaged lines:",
        document.querySelectorAll(
            "#productLinesContainer .line-item.bg-red-50\\/30"
        )
    );
}

// Function to calculate and update the total price for damage products
function calculateDamageTotal() {
    let damageTotal = 0;
    const damageLines = document.querySelectorAll(".line-item.bg-red-50\\/30");

    damageLines.forEach((line) => {
        const quantity = parseFloat(line.querySelector(".quantity").value) || 0;
        const price = parseFloat(line.querySelector(".unit-price").value) || 0;
        const lineTotal = quantity * price;
        line.querySelector(".line-total").textContent = lineTotal.toFixed(2);
        damageTotal += lineTotal;
    });

    // Update damage total in form
    document.getElementById("damageTotal").textContent =
        "৳" + damageTotal.toFixed(2);
    document.getElementById("damageTotalInput").value = damageTotal.toFixed(2);
}

// function to calculate totals
function calculateTotal() {
    let subtotal = 0;
    const regularLines = document.querySelectorAll(
        ".line-item:not(.bg-red-50\\/30)"
    );

    regularLines.forEach((line) => {
        const quantity = parseFloat(line.querySelector(".quantity").value) || 0;
        const price = parseFloat(line.querySelector(".unit-price").value) || 0;
        const lineTotal = quantity * price;
        line.querySelector(".line-total").textContent = lineTotal.toFixed(2);
        subtotal += lineTotal;
    });

    // Update subtotal display
    document.getElementById("subtotal").textContent = "৳" + subtotal.toFixed(2);
    document.getElementById("subtotalInput").value = subtotal.toFixed(2);

    // Update grand total display (same as subtotal for now)
    document.getElementById("grandTotal").textContent =
        "৳" + subtotal.toFixed(2);
    document.getElementById("grandTotalInput").value = subtotal.toFixed(2);
}

// function add product to purchase transection from
function calculateProfit(input) {
    const lineItem = input.closest(".line-item");
    const dp = parseFloat(lineItem.querySelector(".dealer-price").value) || 0;
    const tp = parseFloat(lineItem.querySelector(".trade-price").value) || 0;

    if (dp > 0 && tp > 0) {
        const profitMargin = Math.round(((tp - dp) / dp) * 100);
        lineItem.querySelector(".profit-margin").textContent =
            profitMargin + "%";
    }
}

function addPurchaseProductToTransaction(productData) {
    if (isDuplicateProduct(productData.productId)) {
        Swal.fire({
            icon: "error",
            title: "Duplicate Product",
            text: "This product is already added to the transaction. Please modify the existing line instead of adding it again.",
        });
        return;
    }

    const template = document.getElementById("purchaseLineTemplate");
    const newLine = template.content
        .cloneNode(true)
        .querySelector(".line-item");

    newLine.dataset.productId = productData.productId;
    newLine.dataset.tradePrice = productData.tradePrice;

    const productSelector = newLine.querySelector(".product-selector");
    $(productSelector).val(productData.productId).trigger("change");

    // Get the selected option's batch price
    const selectedOption =
        productSelector.options[productSelector.selectedIndex];
    const batchPrice =
        selectedOption.getAttribute("data-batch-price") ||
        productData.tradePrice;

    const quantityInput = newLine.querySelector(".quantity");
    quantityInput.value = productData.quantity;
    quantityInput.addEventListener("input", calculatePurchaseTotal);

    const dealerPriceInput = newLine.querySelector(".dealer-price");
    dealerPriceInput.addEventListener("input", calculatePurchaseTotal);

    const tradePriceInput = newLine.querySelector(".trade-price");
    tradePriceInput.value = batchPrice; // Use the batch price
    tradePriceInput.addEventListener("input", calculatePurchaseTotal);

    calculateProfit(dealerPriceInput);

    document.querySelector("#productLinesContainer").appendChild(newLine);
    $(newLine).find(".select2").select2();
    calculatePurchaseTotal();
}

function calculatePurchaseTotal() {
    let subtotal = 0;
    const purchaseLines = document.querySelectorAll(".line-item");

    purchaseLines.forEach((line) => {
        const quantity = parseFloat(line.querySelector(".quantity").value) || 0;
        const dealerPrice =
            parseFloat(line.querySelector(".dealer-price").value) || 0;
        const lineTotal = quantity * dealerPrice;
        line.querySelector(".line-total").textContent = lineTotal.toFixed(2);
        subtotal += lineTotal;
    });

    // Update subtotal display
    document.getElementById("subtotal").textContent = "" + subtotal.toFixed(2);
    document.getElementById("subtotalInput").value = subtotal.toFixed(2);

    // Update grand total display
    document.getElementById("grandTotal").textContent =
        "৳" + subtotal.toFixed(2);
    document.getElementById("grandTotalInput").value = subtotal.toFixed(2);
}

// Function to calculate the price diffrence

function calculatePriceDifference(input, showWarning = true) {
    const lineItem = input.closest(".line-item");
    const quantity = parseFloat(lineItem.querySelector(".quantity").value) || 0;
    const unitPrice =
        parseFloat(lineItem.querySelector(".unit-price").value) || 0;
    const tradePrice = parseFloat(lineItem.dataset.tradePrice) || 0;

    // Get the disable_underprice setting from the data attribute
    const disableUnderprice =
        document.querySelector(".container-fluid").dataset.disableUnderprice ===
        "true";
    console.log("Price check:", {
        unitPrice,
        tradePrice,
        disableUnderprice,
        showWarning,
    });

    // Check if underprice is disabled and the unit price is less than trade price
    if (disableUnderprice && unitPrice < tradePrice) {
        // Only show warning if showWarning is true (user is actively changing the price)
        if (showWarning) {
            Swal.fire({
                icon: "warning",
                title: "Price Restriction",
                text: "You can only do overprice, underprice is disabled for you.",
                confirmButtonColor: "#3085d6",
            });
        }

        // Reset the unit price to the trade price
        lineItem.querySelector(".unit-price").value = tradePrice;

        // Recalculate with the corrected price
        const difference = 0; // No difference when price equals trade price
        const priceDiffElement = lineItem.querySelector(".price-difference");
        priceDiffElement.textContent = Math.abs(difference).toFixed(2);
        priceDiffElement.className =
            "price-difference font-medium text-sm text-gray-600";
    } else {
        // Original calculation for when underprice is allowed or price is not under trade price
        const difference = (unitPrice - tradePrice) * quantity;
        const priceDiffElement = lineItem.querySelector(".price-difference");
        priceDiffElement.textContent = Math.abs(difference).toFixed(2);
        priceDiffElement.className =
            "price-difference font-medium text-sm " +
            (difference < 0
                ? "text-red-600"
                : difference > 0
                ? "text-green-600"
                : "text-gray-600");
    }

    updateTotalPriceDifference();
    calculateTotal();
}

function updateTotalPriceDifference() {
    let totalDifference = 0;
    const lineItems = document.querySelectorAll(".line-item");

    lineItems.forEach((line) => {
        const quantity =
            parseFloat(line.querySelector(".quantity")?.value) || 0;
        const unitPrice =
            parseFloat(line.querySelector(".unit-price")?.value) || 0;
        const tradePrice =
            parseFloat(line.querySelector(".trade-price")?.textContent) || 0;

        if (quantity && unitPrice && tradePrice) {
            totalDifference += (unitPrice - tradePrice) * quantity;
        }
    });

    const discountInput = document.getElementById("discount");
    if (discountInput && totalDifference < 0) {
        discountInput.value = Math.abs(totalDifference).toFixed(2);
    }
}
//---------------------------PURCHASE TRANSECTION ENDS HERE ---------------------------
function submitPurchaseTransaction() {
    // Step 1: Initialize submission
    console.log("Step 1: Starting purchase transaction submission");

    // Debug payment method element
    const paymentMethodElement = document.querySelector(
        'select[name="payment_method"]'
    );
    console.log("Payment Method Element:", paymentMethodElement);
    console.log("Payment Method Value:", paymentMethodElement?.value);

    // Step 2: Get and validate form elements
    console.log("Step 2: Collecting form elements");
    const formElements = {
        dateInput: document.querySelector('input[name="transaction_date"]'),
        ledgerSelect: document.querySelector('select[name="ledger_id"]'),
        paymentMethod: document.querySelector('select[name="payment_method"]'),
        subtotalInput: document.getElementById("subtotalInput"),
        roundOffInput: document.getElementById("roundOffInput"),
        discountInput: document.getElementById("discountInput"),
        grandTotalInput: document.getElementById("grandTotalInput"),
    };

    // Step 3: Debug form elements
    console.log("Step 3: Form elements collected:", formElements);

    // Step 4: Validate required elements
    console.log("Step 4: Validating required elements");
    const requiredElements = [
        "dateInput",
        "ledgerSelect",
        "paymentMethod",
        "subtotalInput",
    ];
    const missingElements = requiredElements.filter(
        (elem) => !formElements[elem]
    );

    if (missingElements.length) {
        console.error("Missing elements:", missingElements);
        Swal.fire({
            icon: "error",
            title: "Form Error",
            text: `Missing required elements: ${missingElements.join(", ")}`,
        });
        return;
    }

    // Step 5: Get purchase lines
    console.log("Step 5: Collecting purchase lines");
    const lines = getPurchaseLines();
    console.log("Purchase lines collected:", lines);

    // Step 6: Validate lines
    console.log("Step 6: Validating purchase lines");
    if (!lines.length) {
        console.warn("No purchase lines found");
        Swal.fire({
            icon: "warning",
            title: "No Products Added",
            text: "Please add at least one product to the purchase",
        });
        return;
    }

    // Step 7: Build transaction data
    console.log("Step 7: Building transaction data");
    const transactionData = {
        entry_type: "purchase",
        transaction_date: formElements.dateInput.value,
        ledger_id: formElements.ledgerSelect.value,
        payment_method: formElements.paymentMethod.value,
        subtotal: parseFloat(formElements.subtotalInput.value),
        round_off: parseFloat(formElements.roundOffInput?.value || 0),
        discount: parseFloat(formElements.discountInput?.value || 0),
        grand_total: parseFloat(
            formElements.grandTotalInput?.value ||
                formElements.subtotalInput.value
        ),
        lines: getPurchaseLines(),
    };

    console.log("Transaction data built:", transactionData);

    // Step 8: Submit to server
    console.log("Step 8: Submitting to server");
    fetch('{{ route("admin.inventory.inventory_transactions.store") }}', {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
            Accept: "application/json",
        },
        body: JSON.stringify(transactionData),
    })
        .then((response) => {
            console.log("Step 9: Server response received", response);
            return response.json();
        })
        .then((data) => {
            console.log("Step 10: Processing server response", data);
            if (data.success) {
                Swal.fire({
                    icon: "success",
                    title: "Success",
                    text: "Purchase transaction saved successfully",
                }).then(() => {
                    window.location.href =
                        '{{ route("admin.inventory.inventory_transactions.index") }}';
                });
            } else {
                throw new Error(
                    data.message || "Failed to save purchase transaction"
                );
            }
        })
        .catch((error) => {
            console.error("Step 10: Error processing purchase", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: error.message || "Failed to save purchase transaction",
            });
        });
}

function getPurchaseLines() {
    const lines = [];
    const lineItems = document.querySelectorAll(
        "#productLinesContainer .line-item"
    );

    lineItems.forEach((line, index) => {
        const lineData = {
            product_id: parseInt(line.dataset.productId),
            quantity: parseFloat(line.querySelector(".quantity").value),
            unit_price: parseFloat(line.querySelector(".dealer-price").value),
            trade_price: parseFloat(line.querySelector(".trade-price").value),
            line_total: parseFloat(
                line.querySelector(".line-total").textContent
            ),
        };

        if (Object.values(lineData).every((value) => !isNaN(value))) {
            lines.push(lineData);
        }
    });

    return lines;
}

//---------------------------PURCHASE TRANSECTION ENDS HERE ---------------------------
// ------------------- SALES TRANSECTION STARTS HERE ---------------------------
let isSubmitting = false;

function submitSalesTransaction() {
    console.log("Step 1: Starting sales transaction submission");
    if (isSubmitting) return;
    isSubmitting = true;
    // Change button state to loading
    const submitButton = document.getElementById("submitTransaction");
    const submitIcon = document.getElementById("submitIcon");
    const submitSpinner = document.getElementById("submitSpinner");
    const submitButtonText = document.getElementById("submitButtonText");

    submitIcon.classList.add("hidden");
    submitSpinner.classList.remove("hidden");
    submitButtonText.textContent = "Submitting...";

    const formElements = {
        dateInput: document.querySelector('input[name="transaction_date"]'),
        ledgerSelect: document.querySelector('select[name="ledger_id"]'),
        paymentMethod: document.querySelector('select[name="payment_method"]'),
        subtotalInput: document.getElementById("subtotalInput"),
        roundOffInput: document.getElementById("roundOffInput"),
        discountInput: document.getElementById("discountInput"),
        grandTotalInput: document.getElementById("grandTotalInput"),
    };

    console.log("Step 2: Form elements collected:", formElements);

    const requiredElements = [
        "dateInput",
        "ledgerSelect",
        "paymentMethod",
        "subtotalInput",
    ];
    const missingElements = requiredElements.filter(
        (elem) => !formElements[elem]?.value
    );

    if (missingElements.length) {
        console.error("Missing required elements:", missingElements);
        // Reset button state
        isSubmitting = false;
        submitIcon.classList.remove("hidden");
        submitSpinner.classList.add("hidden");
        submitButtonText.textContent = "Submit Sale";

        Swal.fire({
            icon: "error",
            title: "Form Error || WILL RELOAD THE PAGE",
            text: `Please fill in all required fields: ${missingElements.join(
                ", "
            )}`,
        }).then(() => {
            // Reload the page when user clicks OK on the validation error dialog
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        });
        return;
    }

    const regularLines = getRegularSalesLines();
    console.log("Regular lines collected:", regularLines);

    if (!regularLines.length) {
        Swal.fire({
            icon: "warning",
            title: "No Products Added",
            text: "Please add at least one product to the sale",
        });
        return;
    }

    const transactionData = {
        entry_type: "sale",
        transaction_date: formElements.dateInput.value,
        ledger_id: formElements.ledgerSelect.value,
        payment_method: formElements.paymentMethod.value,
        subtotal: parseFloat(formElements.subtotalInput.value),
        round_off: parseFloat(formElements.roundOffInput?.value || 0),
        discount: parseFloat(formElements.discountInput?.value || 0),
        grand_total: parseFloat(formElements.grandTotalInput.value),
        lines: regularLines,
    };

    console.log("Step 4: Transaction data built:", transactionData);

    fetch('{{ route("admin.inventory.inventory_transactions.store") }}', {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
            Accept: "application/json",
        },
        body: JSON.stringify(transactionData),
    })
        .then((response) => response.json())
        .then((data) => {
            console.log("Transaction response:", data);
            // Create a new transaction ID variable
            let transactionId;

            // Check all possible locations of the transaction ID
            if (data.transaction_id) {
                transactionId = data.transaction_id;
            } else if (data.data && data.data.id) {
                transactionId = data.data.id;
            } else if (data.id) {
                transactionId = data.id;
            }

            const damageLines = getDamageSalesLines();
            console.log(
                "Transaction ID:",
                transactionId,
                "Damage Lines:",
                damageLines
            );

            if (damageLines.length > 0) {
                const damageData = {
                    inventory_transaction_id: transactionId,
                    transaction_date: formElements.dateInput.value,
                    customer_ledger_id: formElements.ledgerSelect.value,
                    damage_lines: damageLines,
                };

                console.log("Submitting damage data:", damageData);

                return fetch(
                    '{{ route("admin.inventory.damage_transactions.store") }}',
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector(
                                'meta[name="csrf-token"]'
                            ).content,
                            Accept: "application/json",
                        },
                        body: JSON.stringify(damageData),
                    }
                ).then((response) => response.json());
            }
            return data;
        })
        .then((finalData) => {
            console.log("Step 7: Final response:", finalData);

            // Reset button state
            submitIcon.classList.remove("hidden");
            submitSpinner.classList.add("hidden");
            submitButtonText.textContent = "Submit Sale";

            Swal.fire({
                icon: "success",
                title: "Success",
                text: "Sales transaction saved successfully",
            }).then(() => {
                window.location.href =
                    '{{ route("admin.inventory.inventory_transactions.index") }}';
            });
        })
        .catch((error) => {
            console.error("Step 8: Error processing sales:", error);
            // Reset button state on error
            isSubmitting = false;
            submitIcon.classList.remove("hidden");
            submitSpinner.classList.add("hidden");
            submitButtonText.textContent = "Submit Sale";

            Swal.fire({
                icon: "error",
                title: "Error || WILL RELOAD THE PAGE",
                text: error.message || "Failed to save sales transaction",
            }).then(() => {
                // Reload the page when user clicks OK on the error dialog
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            });
        });
}

function getRegularSalesLines() {
    const lines = [];
    const regularLines = document.querySelectorAll(
        "#productLinesContainer .line-item:not(.bg-red-50\\/30)"
    );

    regularLines.forEach((line, index) => {
        const quantity = parseFloat(line.querySelector(".quantity").value);
        const unitPrice = parseFloat(line.querySelector(".unit-price").value);
        const tradePrice = parseFloat(line.dataset.tradePrice);
        const priceDifference = (unitPrice - tradePrice) * quantity;

        const lineData = {
            product_id: parseInt(line.dataset.productId),
            quantity: quantity,
            unit_price: unitPrice,
            line_total: parseFloat(
                line.querySelector(".line-total").textContent
            ),
            line_discount: priceDifference,
        };
        lines.push(lineData);
    });

    return lines;
}

function getDamageSalesLines() {
    const damageLines = [];
    const damageLinesElements = document.querySelectorAll(
        "#productLinesContainer .line-item.bg-red-50\\/30"
    );

    console.log("Found damage line elements:", damageLinesElements.length);

    damageLinesElements.forEach((line, index) => {
        const productSelector = line.querySelector(".product-selector");
        const productId = parseInt(productSelector.value);
        const quantity = parseFloat(line.querySelector(".quantity").value);
        const unitPrice = parseFloat(line.querySelector(".unit-price").value);
        const lineTotal = quantity * unitPrice;

        console.log("Processing damage line:", {
            productId,
            quantity,
            unitPrice,
            lineTotal,
        });

        if (productId && quantity && unitPrice) {
            damageLines.push({
                product_id: productId,
                quantity: quantity,
                unit_price: unitPrice,
                line_total: lineTotal,
                damage_reason: "Damaged during delivery",
            });
        }
    });

    console.log("Processed damage lines:", damageLines);
    return damageLines;
}

// ------------------- PRODUCT APPENDING STAFF GOES HERE ---------------------------

function getAppendTransactionLines() {
    const lines = [];
    const lineItems = document.querySelectorAll(
        "#productLinesContainer .line-item:not(.bg-red-50\\/30)"
    );

    lineItems.forEach((line) => {
        const productSelect = line.querySelector(".product-selector");
        if (!productSelect || !productSelect.value) return;

        const selectedOption =
            productSelect.options[productSelect.selectedIndex];
        const batchId = selectedOption.getAttribute("data-batch-id");

        const quantity = parseFloat(line.querySelector(".quantity").value);
        const unitPrice = parseFloat(line.querySelector(".unit-price").value);
        const tradePrice = parseFloat(line.dataset.tradePrice);
        const priceDifference = (unitPrice - tradePrice) * quantity;

        const lineData = {
            product_id: parseInt(line.dataset.productId),
            batch_id: parseInt(batchId),
            quantity: quantity,
            unit_price: unitPrice,
            line_total: parseFloat(
                line.querySelector(".line-total").textContent
            ),
            line_discount: priceDifference,
            trade_price: tradePrice,
        };

        if (lineData.product_id && lineData.batch_id && lineData.quantity > 0) {
            lines.push(lineData);
        }
    });

    return lines;
}

document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM Content Loaded - Starting initialization");
    const isPurchaseForm = document.getElementById("purchaseForm") !== null;
    const isSaleForm = document.getElementById("saleForm") !== null;

    const ledgerSelect = document.querySelector('select[name="ledger_id"]');
    const submitButton = document.getElementById("submitTransaction");
    const appendButton = document.getElementById("appendTransaction");

    console.log("DOM Elements initialized:", {
        ledgerSelect: !!ledgerSelect,
        submitButton: !!submitButton,
        appendButton: !!appendButton,
    });

    if (isPurchaseForm && submitButton) {
        submitButton.style.display = "flex";
        return;
    }

    if (isSaleForm) {
        // Initialize select2 for customer ledger
        $(document).ready(function () {
            const ledgerSelect = document.querySelector(
                'select[name="ledger_id"]'
            );
            const submitButton = document.getElementById("submitTransaction");
            const appendButton = document.getElementById("appendTransaction");
            const locationFilter = document.getElementById("locationFilter");

            // Store all original customer options for filtering
            const allOriginalOptions = Array.from(
                ledgerSelect.querySelectorAll("option")
            );
            console.log(
                "Stored",
                allOriginalOptions.length,
                "original customer options"
            );

            // Function to filter customers by location
            function filterCustomersByLocation(selectedLocation) {
                console.log(
                    "Filtering customers by location:",
                    selectedLocation
                );

                // Get all original options
                const allOptions = allOriginalOptions.slice(); // Use a copy of the original options

                // Clear the current select2 instance
                $("#customerLedgerSelect").val(null).trigger("change");

                // Destroy the current select2 instance
                $("#customerLedgerSelect").select2("destroy");

                // Remove all options except the placeholder
                $(ledgerSelect).find("option:not(:first)").remove();

                // Filter and add back only the matching options
                let matchCount = 0;
                allOptions.forEach((option) => {
                    if (option.value === "") return; // Skip the placeholder

                    const optionLocation = option.getAttribute("data-location");
                    if (
                        !selectedLocation ||
                        optionLocation === selectedLocation
                    ) {
                        ledgerSelect.appendChild(option.cloneNode(true));
                        matchCount++;
                    }
                });

                console.log(
                    `Filtered customers: ${matchCount} matches found for location "${
                        selectedLocation || "All"
                    }"`
                );

                // Reinitialize select2
                $("#customerLedgerSelect").select2({
                    placeholder: "Search and select customer",
                    allowClear: true,
                    width: "100%",
                });

                // Reset buttons
                if (submitButton) submitButton.style.display = "flex";
                if (appendButton) appendButton.style.display = "none";
            }

            // Initialize Select2
            $("#customerLedgerSelect").select2({
                placeholder: "Search and select customer",
                allowClear: true,
                width: "100%",
            });

            // Initialize Select2 for location filter
            $("#locationFilter").select2({
                placeholder: "Search and select location",
                allowClear: true,
                width: "100%",
            });

            // Load saved location from localStorage if it exists
            const savedLocation = localStorage.getItem("selectedLocation");
            if (savedLocation) {
                console.log(
                    "Loading saved location from localStorage:",
                    savedLocation
                );
                // Set the location filter to the saved value
                $("#locationFilter")
                    .val(savedLocation)
                    .trigger("change.select2");
                // Apply the filter immediately to ensure customers are filtered on page load
                filterCustomersByLocation(savedLocation);
            }

            // Location filter functionality - use jQuery on() method for better compatibility
            $("#locationFilter").on("change", function () {
                const selectedLocation = this.value;
                console.log("Location filter changed to:", selectedLocation);

                // Save selected location to localStorage
                if (selectedLocation) {
                    localStorage.setItem("selectedLocation", selectedLocation);
                } else {
                    localStorage.removeItem("selectedLocation");
                }

                // Apply the filter
                filterCustomersByLocation(selectedLocation);
            });

            // Use a debounce flag to prevent multiple executions
            let isProcessing = false;

            // Only bind to the select2:select event
            $("#customerLedgerSelect").on("select2:select", async function (e) {
                // Prevent multiple executions
                if (isProcessing) return;
                isProcessing = true;

                const ledgerId = this.value;
                console.log("Ledger selected:", ledgerId);

                if (!ledgerId) {
                    console.log("No ledger selected, returning");
                    if (submitButton) submitButton.style.display = "flex";
                    if (appendButton) appendButton.style.display = "none";
                    isProcessing = false;
                    return;
                }

                try {
                    console.log(
                        "Checking for existing transactions:",
                        ledgerId
                    );
                    const response = await fetch(
                        `/admin/inventory/check-transactions/${ledgerId}`
                    );
                    const data = await response.json();
                    console.log("Transaction check response:", data);

                    if (data.exists) {
                        console.log(
                            "Existing transaction found:",
                            data.transaction_id
                        );
                        if (submitButton) submitButton.style.display = "none";
                        if (appendButton) {
                            appendButton.style.display = "flex";
                            appendButton.setAttribute(
                                "data-transaction-id",
                                data.transaction_id
                            );
                        }
                    } else {
                        console.log("No existing transaction found");
                        if (submitButton) submitButton.style.display = "flex";
                        if (appendButton) appendButton.style.display = "none";
                    }
                } catch (error) {
                    console.error("Error checking transactions:", error);
                }

                // Reset the processing flag after a short delay
                setTimeout(() => {
                    isProcessing = false;
                }, 100);
            });

            // Handle the clear event from Select2
            $("#customerLedgerSelect").on("select2:clear", function () {
                if (submitButton) submitButton.style.display = "flex";
                if (appendButton) appendButton.style.display = "none";
            });
        });

        appendButton?.addEventListener("click", async function (e) {
            e.preventDefault();
            const transactionId = this.getAttribute("data-transaction-id");
            this.disabled = true;
            // Change button color to red
            this.classList.remove(
                "from-green-500",
                "to-green-600",
                "hover:from-green-600",
                "hover:to-green-700"
            );
            this.classList.add("from-red-500", "to-red-600");

            this.querySelector("#appendIcon").classList.add("hidden");
            this.querySelector("#appendSpinner").classList.remove("hidden");
            this.querySelector("#appendButtonText").textContent = "Adding...";

            const regularLines = getAppendTransactionLines();
            const damageLines = getDamageSalesLines();

            console.log("Collected lines for append:", regularLines);
            console.log("Damage lines:", damageLines);

            if (!regularLines.length && !damageLines.length) {
                console.warn("No products found to append");

                // Reset button state
                this.disabled = false;
                this.classList.remove("from-red-500", "to-red-600");
                this.classList.add(
                    "from-green-500",
                    "to-green-600",
                    "hover:from-green-600",
                    "hover:to-green-700"
                );
                this.querySelector("#appendIcon").classList.remove("hidden");
                this.querySelector("#appendSpinner").classList.add("hidden");
                this.querySelector("#appendButtonText").textContent =
                    "Add to Invoice";

                Swal.fire({
                    icon: "warning",
                    title: "No Products Added || WILL RELOAD THE PAGE",
                    text: "Please add at least one product to append",
                }).then(() => {
                    // Reload the page when user clicks OK
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                });
                return;
            }

            const formElements = {
                dateInput: document.querySelector(
                    'input[name="transaction_date"]'
                ),
                ledgerSelect: document.querySelector(
                    'select[name="ledger_id"]'
                ),
                subtotalInput: document.getElementById("subtotalInput"),
                grandTotalInput: document.getElementById("grandTotalInput"),
            };

            try {
                // First, append regular products
                if (regularLines.length > 0) {
                    const appendData = {
                        transaction_id: transactionId,
                        lines: regularLines,
                        subtotal: parseFloat(formElements.subtotalInput.value),
                        grand_total: parseFloat(
                            formElements.grandTotalInput.value
                        ),
                    };

                    console.log("Submitting append data:", appendData);
                    const appendResponse = await fetch(
                        "/admin/inventory/append-transaction",
                        {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector(
                                    'meta[name="csrf-token"]'
                                ).content,
                                Accept: "application/json",
                            },
                            body: JSON.stringify(appendData),
                        }
                    );
                    const appendResult = await appendResponse.json();
                    if (!appendResult.success) {
                        throw new Error(appendResult.message);
                    }
                }

                // Then, process damage lines if any
                if (damageLines.length > 0) {
                    const damageData = {
                        inventory_transaction_id: transactionId,
                        transaction_date: formElements.dateInput.value,
                        customer_ledger_id: formElements.ledgerSelect.value,
                        damage_lines: damageLines,
                    };

                    const damageResponse = await fetch(
                        '{{ route("admin.inventory.damage_transactions.store") }}',
                        {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector(
                                    'meta[name="csrf-token"]'
                                ).content,
                                Accept: "application/json",
                            },
                            body: JSON.stringify(damageData),
                        }
                    );
                }

                // Success handling
                Swal.fire({
                    icon: "success",
                    title: "Success",
                    text: "Products added to existing invoice successfully",
                }).then(() => {
                    window.location.href =
                        "/admin/inventory/inventory_transactions";
                });
            } catch (error) {
                console.error("Error processing transaction:", error);
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: error.message || "Failed to process transaction",
                });
            } finally {
                this.querySelector("#appendIcon").classList.remove("hidden");
                this.querySelector("#appendSpinner").classList.add("hidden");
                this.querySelector("#appendButtonText").textContent =
                    "Add to Invoice";
            }
        });
    }
});

const DAMAGE_STORE_URL =
    "{{ route('admin.inventory.damage_transactions.store') }}";
