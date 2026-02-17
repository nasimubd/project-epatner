<!DOCTYPE html>
<html>

<head>
    <title>Test Duplicate Detection & Merge</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input,
        select {
            width: 300px;
            padding: 8px;
            border: 1px solid #ddd;
        }

        button {
            background: #007cba;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            margin-right: 10px;
        }

        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .results {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }

        .duplicate-item {
            margin: 10px 0;
            padding: 15px;
            background: white;
            border-left: 4px solid #ff6b6b;
            border-radius: 4px;
        }

        .confidence {
            font-weight: bold;
            color: #007cba;
        }

        .risk-high {
            border-left-color: #ff6b6b;
        }

        .risk-medium {
            border-left-color: #ffa726;
        }

        .risk-low {
            border-left-color: #66bb6a;
        }

        .merge-section {
            margin-top: 15px;
            padding: 15px;
            background: #f0f8ff;
            border: 1px solid #007cba;
            border-radius: 4px;
        }

        .merge-strategy {
            margin: 10px 0;
        }

        .merge-strategy input[type="radio"] {
            width: auto;
            margin-right: 8px;
        }

        .merge-strategy label {
            display: inline;
            font-weight: normal;
            margin-left: 5px;
        }

        .customer-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 15px 0;
        }

        .customer-card {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fafafa;
        }

        .merge-btn {
            background: #28a745;
        }

        .merge-btn:hover {
            background: #218838;
        }

        .cancel-btn {
            background: #6c757d;
        }

        .cancel-btn:hover {
            background: #5a6268;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin: 10px 0;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <h1>Test Duplicate Detection & Customer Merge</h1>

    <form id="duplicateTestForm">
        <div class="form-group">
            <label for="name">Customer Name:</label>
            <input type="text" id="name" name="name" placeholder="Enter customer name">
        </div>

        <div class="form-group">
            <label for="phone">Phone Number:</label>
            <input type="text" id="phone" name="phone" placeholder="Enter phone number">
        </div>

        <div class="form-group">
            <label for="district">District:</label>
            <input type="text" id="district" name="district" placeholder="Enter district">
        </div>

        <div class="form-group">
            <label for="sub_district">Sub District:</label>
            <input type="text" id="sub_district" name="sub_district" placeholder="Enter sub district">
        </div>

        <div class="form-group">
            <label for="village">Village:</label>
            <input type="text" id="village" name="village" placeholder="Enter village">
        </div>

        <button type="submit">Check for Duplicates</button>
    </form>

    <div id="results" class="results" style="display: none;">
        <h3>Duplicate Detection Results</h3>
        <div id="duplicatesList"></div>
    </div>

    <!-- Merge Modal -->
    <div id="mergeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; max-width: 800px; width: 90%; max-height: 90%; overflow-y: auto;">
            <h3>Merge Customers</h3>
            <div id="mergeContent"></div>
            <div style="margin-top: 20px; text-align: right;">
                <button type="button" id="cancelMerge" class="cancel-btn">Cancel</button>
                <button type="button" id="confirmMerge" class="merge-btn">Confirm Merge</button>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Set up CSRF token for AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            let currentNewCustomerData = {};
            let selectedDuplicate = null;
            let selectedMergeStrategy = 'keep_existing';

            $('#duplicateTestForm').on('submit', function(e) {
                e.preventDefault();

                const formData = {
                    name: $('#name').val(),
                    phone: $('#phone').val(),
                    district: $('#district').val(),
                    sub_district: $('#sub_district').val(),
                    village: $('#village').val()
                };

                currentNewCustomerData = formData;

                // Show loading
                $('#results').show();
                $('#duplicatesList').html('<p>Checking for duplicates...</p>');

                $.ajax({
                    url: '{{ route("super-admin.customer-ledgers.check-duplicates") }}',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        displayResults(response);
                    },
                    error: function(xhr, status, error) {
                        $('#duplicatesList').html('<p style="color: red;">Error: ' + error + '</p>');
                        console.error('Error:', xhr.responseText);
                    }
                });

            });

            function displayResults(duplicates) {
                if (duplicates.length === 0) {
                    $('#duplicatesList').html('<p style="color: green;">No duplicates found! You can create this customer.</p>');
                    return;
                }

                let html = '<h4>Found ' + duplicates.length + ' potential duplicate(s):</h4>';

                duplicates.forEach(function(duplicate, index) {
                    const riskClass = 'risk-' + (duplicate.type === 'phone' ? 'high' :
                        duplicate.similarity >= 80 ? 'high' :
                        duplicate.similarity >= 60 ? 'medium' : 'low');

                    html += '<div class="duplicate-item ' + riskClass + '">';
                    html += '<div><strong>Match #' + (index + 1) + '</strong></div>';
                    html += '<div><strong>Customer:</strong> ' + duplicate.customer.ledger_name + '</div>';
                    html += '<div><strong>Phone:</strong> ' + (duplicate.customer.contact_number || 'N/A') + '</div>';
                    html += '<div><strong>Location:</strong> ' +
                        (duplicate.customer.village || '') + ', ' +
                        (duplicate.customer.sub_district || '') + ', ' +
                        (duplicate.customer.district || '') + '</div>';
                    html += '<div><strong>Type:</strong> ' + duplicate.type + '</div>';
                    html += '<div class="confidence"><strong>Confidence:</strong> ' + duplicate.similarity + '%</div>';
                    html += '<div><strong>Reason:</strong> ' + duplicate.reason + '</div>';
                    html += '<div><strong>Customer ID:</strong> ' + duplicate.customer.ledger_id + '</div>';
                    html += '<div><strong>Created:</strong> ' + new Date(duplicate.customer.created_at).toLocaleDateString() + '</div>';

                    // Add merge button for high confidence duplicates
                    if (duplicate.similarity >= 70 || duplicate.type === 'phone') {
                        html += '<div style="margin-top: 10px;">';
                        html += '<button type="button" class="merge-btn" onclick="openMergeModal(' + duplicate.customer.ledger_id + ', \'' + duplicate.customer.ledger_name + '\')">Merge with this customer</button>';
                        html += '</div>';
                    }

                    html += '</div>';
                });

                $('#duplicatesList').html(html);
            }

            // Merge functionality
            window.openMergeModal = function(customerId, customerName) {
                selectedDuplicate = customerId;

                // Get existing customer details using dedicated AJAX route
                $.ajax({
                    url: '{{ route("super-admin.customer-ledgers.get-customer-data", ":id") }}'.replace(':id', customerId),
                    method: 'GET',
                    success: function(response) {
                        showMergeModal(response, customerName);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading customer details:', xhr.responseText);
                        alert('Error loading customer details: ' + error);
                    }
                });
            };



            function showMergeModal(existingCustomer, customerName) {
                let html = '<div class="customer-details">';

                // Existing customer
                html += '<div class="customer-card">';
                html += '<h4>Existing Customer</h4>';
                html += '<p><strong>Name:</strong> ' + customerName + '</p>';
                html += '<p><strong>Phone:</strong> ' + (existingCustomer.contact_number || 'N/A') + '</p>';
                html += '<p><strong>Location:</strong> ' + (existingCustomer.district || '') + ', ' + (existingCustomer.sub_district || '') + ', ' + (existingCustomer.village || '') + '</p>';
                html += '<p><strong>Created:</strong> ' + new Date(existingCustomer.created_at).toLocaleDateString() + '</p>';
                html += '</div>';

                // New customer data
                html += '<div class="customer-card">';
                html += '<h4>New Customer Data</h4>';
                html += '<p><strong>Name:</strong> ' + currentNewCustomerData.name + '</p>';
                html += '<p><strong>Phone:</strong> ' + (currentNewCustomerData.phone || 'N/A') + '</p>';
                html += '<p><strong>Location:</strong> ' + (currentNewCustomerData.district || '') + ', ' + (currentNewCustomerData.sub_district || '') + ', ' + (currentNewCustomerData.village || '') + '</p>';
                html += '</div>';

                html += '</div>';

                // Merge strategy options
                html += '<div class="merge-section">';
                html += '<h4>Choose Merge Strategy:</h4>';

                html += '<div class="merge-strategy">';
                html += '<input type="radio" id="keep_existing" name="merge_strategy" value="keep_existing" checked>';
                html += '<label for="keep_existing">Keep Existing Customer (update with missing data from new)</label>';
                html += '</div>';

                html += '<div class="merge-strategy">';
                html += '<input type="radio" id="keep_new" name="merge_strategy" value="keep_new">';
                html += '<label for="keep_new">Replace with New Customer Data (mark existing as merged)</label>';
                html += '</div>';

                html += '<div class="merge-strategy">';
                html += '<input type="radio" id="merge_data" name="merge_strategy" value="merge_data">';
                html += '<label for="merge_data">Merge Best Data from Both (create new merged record)</label>';
                html += '</div>';

                html += '</div>';

                $('#mergeContent').html(html);
                $('#mergeModal').show();

                // Handle strategy selection
                $('input[name="merge_strategy"]').on('change', function() {
                    selectedMergeStrategy = $(this).val();
                });
            }

            $('#cancelMerge').on('click', function() {
                $('#mergeModal').hide();
                selectedDuplicate = null;
            });

            $('#confirmMerge').on('click', function() {
                if (!selectedDuplicate) {
                    alert('No customer selected for merge');
                    return;
                }

                // Disable button and show loading
                $('#confirmMerge').prop('disabled', true).text('Merging...');

                $.ajax({
                    url: '/super-admin/customer-ledgers/merge-duplicates',
                    method: 'POST',
                    data: {
                        existing_customer_id: selectedDuplicate,
                        new_customer_data: currentNewCustomerData,
                        merge_strategy: selectedMergeStrategy
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#mergeModal').hide();
                            $('#duplicatesList').html('<div class="success-message">Customers merged successfully! Primary Customer ID: ' + response.primary_customer_id + '</div>');

                            // Clear form
                            $('#duplicateTestForm')[0].reset();
                        } else {
                            alert('Merge failed: ' + response.message);
                        }
                    },
                    error: function(xhr) {
                        const response = JSON.parse(xhr.responseText);
                        alert('Merge failed: ' + response.message);
                    },
                    complete: function() {
                        $('#confirmMerge').prop('disabled', false).text('Confirm Merge');
                    }
                });
            });

            // Close modal when clicking outside
            $('#mergeModal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                    selectedDuplicate = null;
                }
            });
        });
    </script>
</body>

</html>