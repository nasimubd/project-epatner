document.addEventListener("DOMContentLoaded", function () {
    // Get the total sales card element
    const totalSalesCard = document.getElementById("totalSalesCard");

    if (totalSalesCard) {
        // Add click event listener to the total sales card
        totalSalesCard.addEventListener("click", function () {
            showSalesBreakdownModal();
        });
    }

    // Function to show the sales breakdown modal
    function showSalesBreakdownModal() {
        // Get date range values from the dashboard filters
        const startDate =
            document.getElementById("start_date")?.value ||
            new Date().toISOString().split("T")[0];
        const endDate =
            document.getElementById("end_date")?.value ||
            new Date().toISOString().split("T")[0];

        // Create modal container if it doesn't exist
        let modalContainer = document.getElementById("salesBreakdownModal");
        if (!modalContainer) {
            modalContainer = document.createElement("div");
            modalContainer.id = "salesBreakdownModal";
            modalContainer.className = "fixed inset-0 z-50 overflow-y-auto";
            modalContainer.innerHTML = `
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                        <div class="absolute top-0 right-0 pt-4 pr-4">
                            <button type="button" id="closeSalesBreakdownModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div id="salesBreakdownContent" class="p-6">
                            <div class="flex justify-center">
                                <svg class="animate-spin h-8 w-8 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modalContainer);

            // Add event listener to close button
            document
                .getElementById("closeSalesBreakdownModal")
                ?.addEventListener("click", function () {
                    modalContainer.remove();
                });
        }

        // Fetch sales breakdown data
        fetch(
            `/admin/dashboard/sales-breakdown?start_date=${encodeURIComponent(
                startDate
            )}&end_date=${encodeURIComponent(endDate)}`
        )
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                // Log the response for debugging
                return response.text().then((text) => {
                    try {
                        // Try to parse as JSON
                        return JSON.parse(text);
                    } catch (e) {
                        // If parsing fails, log the raw response and throw an error
                        console.error("Invalid JSON response:", text);
                        throw new Error(
                            "Server returned invalid JSON: " +
                                text.substring(0, 100) +
                                "..."
                        );
                    }
                });
            })
            .then((data) => {
                const contentDiv = document.getElementById(
                    "salesBreakdownContent"
                );

                // Check if we have categories data
                if (!data.categories || data.categories.length === 0) {
                    contentDiv.innerHTML = `
                        <div class="text-center text-gray-500">
                            <p>No sales data available for the selected period.</p>
                        </div>
                    `;
                    return;
                }

                // Calculate total amount
                const totalAmount = data.categories.reduce(
                    (sum, category) => sum + category.total_sales,
                    0
                );

                // Generate HTML for the table
                let html = `
                    <h3 class="text-lg font-semibold mb-4">Category-wise Sales Breakdown</h3>
                    <p class="text-sm text-gray-600 mb-4">Period: ${formatDate(
                        data.start_date
                    )} to ${formatDate(data.end_date)}</p>
                    
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
                `;

                // Add rows for each category
                let totalQuantity = 0;
                data.categories.forEach((category) => {
                    totalQuantity += category.total_quantity;
                    const percentage =
                        (category.total_sales / totalAmount) * 100;
                    html += `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${
                                category.name
                            }</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">${numberFormat(
                                category.total_quantity
                            )}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">৳${numberFormat(
                                category.total_sales,
                                2
                            )}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">${percentage.toFixed(
                                1
                            )}%</td>
                        </tr>
                    `;
                });

                // Add footer with totals
                html += `
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">${numberFormat(
                                        totalQuantity
                                    )}</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">৳${numberFormat(
                                        totalAmount,
                                        2
                                    )}</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">100%</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                `;

                contentDiv.innerHTML = html;
            })
            .catch((error) => {
                console.error("Error fetching sales breakdown:", error);
                document.getElementById("salesBreakdownContent").innerHTML = `
                    <div class="text-center text-red-500">
                        <p>Error loading sales breakdown data. Please try again.</p>
                        <p class="text-sm mt-2">${error.message}</p>
                    </div>
                `;
            });
    }

    // Helper function to format numbers with commas
    function numberFormat(number, decimals = 0) {
        return new Intl.NumberFormat("en-US", {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        }).format(number);
    }

    // Helper function to format dates
    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { year: "numeric", month: "short", day: "numeric" };
        return date.toLocaleDateString("en-US", options);
    }
});
