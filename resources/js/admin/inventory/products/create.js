// Wait for the DOM to be fully loaded
document.addEventListener("DOMContentLoaded", function () {
    // Button handling
    const cancelBtn = document.getElementById("cancelBtn");
    const createProductBtn = document.getElementById("createProductBtn");

    if (cancelBtn) {
        cancelBtn.addEventListener("click", function () {
            document.getElementById("cancelIcon").classList.add("hidden");
            document.getElementById("cancelSpinner").classList.remove("hidden");
            document.getElementById("cancelButtonText").textContent =
                "Canceling...";
        });
    }

    if (createProductBtn) {
        createProductBtn.addEventListener("click", function () {
            document.getElementById("createIcon").classList.add("hidden");
            document.getElementById("createSpinner").classList.remove("hidden");
            document.getElementById("createButtonText").textContent =
                "Creating...";
        });
    }

    // Price calculation
    const dealerPriceInput = document.getElementById("dealer_price");
    const profitMarginInput = document.getElementById("profit_margin");
    const tradePriceInput = document.getElementById("trade_price");

    function calculateTradePrice() {
        const dealerPrice = parseFloat(dealerPriceInput.value) || 0;
        const profitMargin = parseFloat(profitMarginInput.value) || 0;
        const tradePrice = dealerPrice + dealerPrice * (profitMargin / 100);
        tradePriceInput.value = tradePrice.toFixed(2);
    }

    if (dealerPriceInput && profitMarginInput) {
        dealerPriceInput.addEventListener("input", calculateTradePrice);
        profitMarginInput.addEventListener("input", calculateTradePrice);
    }

    // Barcode generation
    const generateBarcodeBtn = document.getElementById("generate-barcode");
    if (generateBarcodeBtn) {
        generateBarcodeBtn.addEventListener("click", function () {
            // The route will be passed from the blade file
            fetch(window.barcodeGenerateRoute)
                .then((response) => response.json())
                .then((data) => {
                    document.getElementById("barcode").value = data.barcode;
                });
        });
    }

    // Date handling
    const openingDateInput = document.getElementById("opening_date");
    const expiryDateInput = document.getElementById("expiry_date");

    if (openingDateInput && expiryDateInput) {
        // Set current date for opening_date
        const today = new Date();
        openingDateInput.valueAsDate = today;

        // Set expiry date to 1 month after opening date
        const nextMonth = new Date(today);
        nextMonth.setMonth(nextMonth.getMonth() + 1);
        expiryDateInput.valueAsDate = nextMonth;

        // Update expiry date when opening date changes
        openingDateInput.addEventListener("change", function () {
            const newOpeningDate = new Date(this.value);
            const newExpiryDate = new Date(newOpeningDate);
            newExpiryDate.setMonth(newExpiryDate.getMonth() + 1);
            expiryDateInput.valueAsDate = newExpiryDate;
        });
    }

    // Set opening stock to 0
    const openingStockInput = document.getElementById("opening_stock");
    if (openingStockInput) {
        openingStockInput.value = "0";
    }
});

// Unit change handler (needs to be global as it's called from HTML)
window.handleUnitChange = function (select) {
    const unitType = select.options[select.selectedIndex].dataset.type;
    const openingStockInput = document.getElementById("opening_stock");

    if (unitType === "fraction") {
        openingStockInput.step = "0.001";
        openingStockInput.pattern = "^d*.?d{0,3}$";
        openingStockInput.placeholder = "e.g. 10.250";
    } else {
        openingStockInput.step = "1";
        openingStockInput.pattern = "^d+$";
        openingStockInput.placeholder = "e.g. 100";
    }
};
