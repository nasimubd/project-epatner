@extends('super-admin.layouts.app')

@section('content')
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Import Products</h2>
            <p class="text-gray-600 mt-1">Upload a CSV or Excel file to import multiple products at once</p>
        </div>

        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
            <h3 class="font-semibold text-blue-800 mb-2">Instructions</h3>
            <ul class="list-disc pl-5 text-sm text-blue-700 space-y-1">
                <li>File must be in CSV or Excel format (.csv, .xlsx, .xls)</li>
                <li>First row should contain column headers</li>
                <li>Required columns: product_name, category_id, unit_id</li>
                <li>Optional columns: barcode, image_url</li>
                <li>Maximum file size: 10MB</li>
                <li>For large imports (1000+ products), the process may take some time</li>
                <li>You will receive a notification when the import is complete</li>
            </ul>
            <div class="mt-3">
                <a href="{{ route('super-admin.common-products.import-template') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                    Download sample template
                </a>
            </div>
        </div>

        <form action="{{ route('super-admin.common-products.process-import') }}" method="POST" enctype="multipart/form-data" id="importForm">
            @csrf

            <div class="mb-6">
                <label for="import_file" class="block text-sm font-medium text-gray-700 mb-1">Import File</label>
                <input type="file" name="import_file" id="import_file"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('import_file') border-red-500 @enderror"
                    accept=".csv,.xlsx,.xls" required>
                @error('import_file')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Progress Bar (hidden by default) -->
            <div id="progressContainer" class="mb-6 hidden">
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
                <div class="flex justify-between mt-2 text-sm text-gray-600">
                    <span id="progressStatus">Processing...</span>
                    <span id="progressPercentage">0%</span>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('super-admin.common-products.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cancel
                </a>
                <button type="submit" id="importButton" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition flex items-center">
                    <span id="importButtonText">Start Import</span>
                    <svg id="importSpinner" class="hidden w-5 h-5 ml-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('importForm');
        const importButton = document.getElementById('importButton');
        const importButtonText = document.getElementById('importButtonText');
        const importSpinner = document.getElementById('importSpinner');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressStatus = document.getElementById('progressStatus');
        const progressPercentage = document.getElementById('progressPercentage');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Show loading state
            importButtonText.textContent = 'Processing...';
            importSpinner.classList.remove('hidden');
            importButton.disabled = true;

            // Show progress bar
            progressContainer.classList.remove('hidden');

            // Create FormData object
            const formData = new FormData(form);

            // Send AJAX request
            fetch("{{ route('super-admin.common-products.process-import') }}", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Start polling for progress updates
                        const jobId = data.job_id;
                        pollImportProgress(jobId);
                    } else {
                        // Handle error
                        progressStatus.textContent = 'Error: ' + data.message;
                        resetImportButton();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    progressStatus.textContent = 'Error occurred during import';
                    resetImportButton();
                });
        });

        function pollImportProgress(jobId) {
            const interval = setInterval(() => {
                fetch(`{{ route('super-admin.common-products.import-progress') }}?job_id=${jobId}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Update progress bar
                        const percent = data.progress;
                        progressBar.style.width = `${percent}%`;
                        progressPercentage.textContent = `${percent}%`;

                        if (data.status === 'completed') {
                            clearInterval(interval);
                            progressStatus.textContent = 'Import completed successfully!';

                            // Redirect after a short delay
                            setTimeout(() => {
                                window.location.href = "{{ route('super-admin.common-products.index') }}";
                            }, 2000);
                        } else if (data.status === 'failed') {
                            clearInterval(interval);
                            progressStatus.textContent = 'Import failed: ' + data.message;
                            resetImportButton();
                        }
                    })
                    .catch(error => {
                        console.error('Error polling progress:', error);
                        clearInterval(interval);
                        progressStatus.textContent = 'Error tracking import progress';
                        resetImportButton();
                    });
            }, 2000); // Poll every 2 seconds
        }

        function resetImportButton() {
            importButtonText.textContent = 'Start Import';
            importSpinner.classList.add('hidden');
            importButton.disabled = false;
        }
    });
</script>
@endpush
@endsection