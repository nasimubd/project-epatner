// Invoice Index Page JavaScript
(function ($) {
    "use strict";

    // Global variables
    var selectedInvoices = [];
    var currentPage = 1;
    var isLoading = false;
    var searchTimeout;

    // Initialize when document is ready
    $(document).ready(function () {
        initializeInvoiceIndex();
    });

    function initializeInvoiceIndex() {
        // Get current page from data attribute
        var pageElement = document.querySelector("[data-current-page]");
        if (pageElement) {
            currentPage =
                parseInt(pageElement.getAttribute("data-current-page")) || 1;
        }

        // Initialize all event handlers
        initializeButtonStates();
        initializeSearchFunctionality();
        initializeFilterHandlers();
        initializeCheckboxFunctionality();
        initializePrintFunctionality();
        initializeLoadMoreFunctionality();
        initializeKeyboardShortcuts();
        initializeUIEnhancements();

        // Initialize selected count
        updateSelectedCount();
    }

    function initializeButtonStates() {
        // Create Invoice button loading state
        $("#createInvoiceBtn").on("click", function () {
            var icon = $("#defaultPlusIcon");
            var spinner = $("#spinnerIcon");
            var text = $("#buttonText");

            icon.addClass("hidden");
            spinner.removeClass("hidden");
            text.text("Loading...");
        });

        // Filter button loading state
        $("#filterBtn").on("click", function () {
            var btn = $(this);
            var originalHtml = btn.html();

            btn.html(
                '<i class="fas fa-spinner fa-spin mr-1"></i><span class="hidden sm:inline">Filtering...</span>'
            );
            btn.prop("disabled", true);
        });
    }

    function initializeSearchFunctionality() {
        $("#search").on("input", function () {
            clearTimeout(searchTimeout);
            var searchTerm = $(this).val();

            if (searchTerm.length > 2) {
                searchTimeout = setTimeout(function () {
                    $("#filter-form").submit();
                }, 1000);
            } else if (searchTerm.length === 0) {
                searchTimeout = setTimeout(function () {
                    $("#filter-form").submit();
                }, 500);
            }
        });
    }

    function initializeFilterHandlers() {
        $("#start_date, #end_date, #entry_type").on("change", function () {
            $("#filter-form").submit();
        });

        // Enhanced form validation
        $("#filter-form").on("submit", function () {
            var submitBtn = $(this).find('button[type="submit"]');
            if (submitBtn.length) {
                submitBtn.prop("disabled", true);
                setTimeout(function () {
                    submitBtn.prop("disabled", false);
                }, 3000);
            }
        });
    }

    function initializeCheckboxFunctionality() {
        // Select all functionality
        $("#select-all, #select-all-desktop").on("change", function () {
            var isChecked = $(this).is(":checked");
            $(".invoice-checkbox").prop("checked", isChecked);

            // Sync both select-all checkboxes
            $("#select-all, #select-all-desktop").prop("checked", isChecked);

            updateSelectedCount();
        });

        // Individual checkbox change
        $(document).on("change", ".invoice-checkbox", function () {
            updateSelectedCount();

            // Update select-all checkboxes
            var totalCheckboxes = $(".invoice-checkbox").length;
            var checkedCheckboxes = $(".invoice-checkbox:checked").length;

            $("#select-all, #select-all-desktop").prop(
                "checked",
                totalCheckboxes === checkedCheckboxes
            );
        });
    }

    function updateSelectedCount() {
        var count = $(".invoice-checkbox:checked").length;
        $("#selected-count").text(count + " selected");

        if (count > 0) {
            $("#selected-count").addClass("has-selection");
            $("#print-selected").prop("disabled", false);
        } else {
            $("#selected-count").removeClass("has-selection");
            $("#print-selected").prop("disabled", true);
        }
    }

    function initializePrintFunctionality() {
        // Print selected functionality
        $("#print-selected").on("click", function () {
            var selectedIds = [];
            var selectedNumbers = [];

            $(".invoice-checkbox:checked").each(function () {
                selectedIds.push($(this).val());
                selectedNumbers.push($(this).data("invoice-id"));
            });

            if (selectedIds.length === 0) {
                showAlert(
                    "Please select at least one invoice to print.",
                    "warning"
                );
                return;
            }

            // Show loading state
            $(this).addClass("btn-loading").prop("disabled", true);

            // Trigger print modal or direct print
            if (typeof window.openPrintModal === "function") {
                window.openPrintModal(selectedIds, selectedNumbers);
            } else {
                // Fallback to direct print
                var printUrl =
                    "/admin/invoices/print?ids=" + selectedIds.join(",");
                window.open(printUrl, "_blank");
            }

            // Reset button state
            var self = this;
            setTimeout(function () {
                $(self).removeClass("btn-loading").prop("disabled", false);
            }, 2000);
        });

        // Single print functionality
        $(document).on("click", ".print-single-btn", function () {
            var invoiceId = $(this).data("invoice-id");
            var invoiceNumber = $(this).data("invoice-number");

            $(this).addClass("btn-loading");

            if (typeof window.openPrintModal === "function") {
                window.openPrintModal([invoiceId], [invoiceNumber]);
            } else {
                var printUrl = "/admin/invoices/print?ids=" + invoiceId;
                window.open(printUrl, "_blank");
            }

            var self = this;
            setTimeout(function () {
                $(self).removeClass("btn-loading");
            }, 2000);
        });
    }

    function initializeLoadMoreFunctionality() {
        $("#load-more").on("click", function () {
            if (isLoading) return;

            isLoading = true;
            var btn = $(this);
            var nextPage = btn.data("page");

            // Show loading state
            btn.addClass("btn-loading").prop("disabled", true);
            btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Loading...');

            // Get current filter values
            var formData = $("#filter-form").serialize();
            var ajaxUrl = btn.data("ajax-url") || "/admin/invoices";

            $.ajax({
                url: ajaxUrl,
                method: "GET",
                data: formData + "&page=" + nextPage,
                success: function (response) {
                    handleLoadMoreSuccess(response, btn, nextPage);
                },
                error: function () {
                    showAlert(
                        "Failed to load more invoices. Please try again.",
                        "error"
                    );
                },
                complete: function () {
                    isLoading = false;
                    btn.removeClass("btn-loading").prop("disabled", false);
                    btn.html(
                        '<i class="fas fa-plus-circle mr-2"></i>Load More Invoices'
                    );
                },
            });
        });
    }

    function handleLoadMoreSuccess(response, btn, nextPage) {
        // Parse the response to extract invoice data
        var $response = $(response);
        var $newMobileItems = $response.find("#mobile-invoices").children();
        var $newDesktopRows = $response.find("#desktop-invoices").children();

        // Add fade-in animation
        $newMobileItems.addClass("fade-in");
        $newDesktopRows.addClass("fade-in");

        // Append new items
        $("#mobile-invoices").append($newMobileItems);
        $("#desktop-invoices").append($newDesktopRows);

        // Update page number
        btn.data("page", nextPage + 1);

        // Check if there are more pages
        var hasMore = $response.find("#load-more").length > 0;
        if (!hasMore) {
            btn.hide();
        }
    }

    function initializeKeyboardShortcuts() {
        $(document).keydown(function (e) {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 75) {
                e.preventDefault();
                $("#search").focus();
            }

            // Escape to clear search
            if (e.keyCode === 27) {
                $("#search").val("").trigger("input");
            }

            // Ctrl/Cmd + P to print selected
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 80) {
                e.preventDefault();
                if ($(".invoice-checkbox:checked").length > 0) {
                    $("#print-selected").click();
                }
            }
        });
    }

    function initializeUIEnhancements() {
        // Auto-hide success messages
        setTimeout(function () {
            $(".alert-success").fadeOut("slow");
            $(".bg-green-100").fadeOut("slow");
        }, 5000);

        // Smooth scroll to top functionality
        $(window).scroll(function () {
            if ($(this).scrollTop() > 100) {
                if (!$("#backToTop").length) {
                    var backToTopHtml =
                        '<button id="backToTop" class="fixed bottom-4 right-4 bg-green-500 hover:bg-green-600 text-white p-3 rounded-full shadow-lg transition-all duration-300 z-50">' +
                        '<i class="fas fa-arrow-up"></i>' +
                        "</button>";
                    $("body").append(backToTopHtml);
                }
                $("#backToTop").fadeIn();
            } else {
                $("#backToTop").fadeOut();
            }
        });

        // Back to top click handler
        $(document).on("click", "#backToTop", function () {
            $("html, body").animate({ scrollTop: 0 }, 600);
        });

        // Enhanced table row hover effects
        $("tbody tr").hover(
            function () {
                $(this).addClass("shadow-sm");
            },
            function () {
                $(this).removeClass("shadow-sm");
            }
        );

        // Focus management for better UX
        var searchInput = document.getElementById("search");
        if (searchInput && searchInput.value === "") {
            searchInput.focus();
        }
    }

    // Helper function for alerts
    function showAlert(message, type) {
        type = type || "info";

        var bgClass = "bg-blue-500";
        var iconClass = "fa-info-circle";

        if (type === "success") {
            bgClass = "bg-green-500";
            iconClass = "fa-check-circle";
        } else if (type === "error") {
            bgClass = "bg-red-500";
            iconClass = "fa-exclamation-circle";
        } else if (type === "warning") {
            bgClass = "bg-yellow-500";
            iconClass = "fa-exclamation-triangle";
        }

        var alertHtml =
            '<div class="fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg text-white ' +
            bgClass +
            ' transition-all duration-300 transform translate-x-full alert-notification">' +
            '<div class="flex items-center">' +
            '<i class="fas ' +
            iconClass +
            ' mr-2"></i>' +
            "<span>" +
            message +
            "</span>" +
            "</div>" +
            "</div>";

        var alertDiv = $(alertHtml);
        $("body").append(alertDiv);

        // Animate in
        setTimeout(function () {
            alertDiv.removeClass("translate-x-full");
        }, 100);

        // Auto remove after 4 seconds
        setTimeout(function () {
            alertDiv.addClass("translate-x-full");
            setTimeout(function () {
                alertDiv.remove();
            }, 300);
        }, 4000);
    }

    // Make functions globally accessible if needed
    window.InvoiceIndex = {
        updateSelectedCount: updateSelectedCount,
        showAlert: showAlert,
    };
})(jQuery);
