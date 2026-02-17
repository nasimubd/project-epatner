@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-lg">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-semibold text-gray-800">Shopfront Images</h2>
                <a href="{{ route('admin.shopfront.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                    Back to Shopfront
                </a>
            </div>
        </div>

        <div class="p-6">
            <!-- Hero Banner Section -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Hero Banner</h3>

                @if($heroBanner)
                <div class="mb-4">
                    <img src="data:image/jpeg;base64,{{ base64_encode($heroBanner->image) }}"
                        alt="Hero Banner"
                        class="w-full max-w-2xl h-64 object-cover rounded-lg shadow-md">
                    <div class="mt-2 flex space-x-2">
                        <button onclick="deleteImage({{ $heroBanner->id }})"
                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                            Delete
                        </button>
                    </div>
                </div>
                @endif

                <form id="heroBannerForm" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload Hero Banner</label>
                        <input type="file"
                            name="hero_banner"
                            id="hero_banner"
                            accept="image/*"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-500 mt-1">Maximum file size: 5MB. Recommended size: 1920x1080px</p>
                    </div>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        Upload Hero Banner
                    </button>
                </form>
            </div>

            <!-- General Category Images Section -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">General Category Images</h3>

                @if($generalCategories && count($generalCategories) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($generalCategories as $generalName => $generalCategory)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3">{{ $generalName }}</h4>
                        <p class="text-sm text-gray-600 mb-3">{{ count($generalCategory['subcategories']) }} subcategories</p>

                        @if(isset($generalCategoryImages[$generalName]))
                        <div class="mb-3">
                            <img src="data:image/jpeg;base64,{{ base64_encode($generalCategoryImages[$generalName]->image) }}"
                                alt="{{ $generalName }}"
                                class="w-full h-32 object-cover rounded-md shadow-sm">
                            <div class="mt-2">
                                <button onclick="deleteImage({{ $generalCategoryImages[$generalName]->id }})"
                                    class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs">
                                    Delete
                                </button>
                            </div>
                        </div>
                        @endif

                        <form class="generalCategoryImageForm" data-general-category="{{ $generalName }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="general_category_name" value="{{ $generalName }}">
                            <div class="space-y-2">
                                <input type="file"
                                    name="general_category_image"
                                    accept="image/*"
                                    class="block w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                                <button type="submit"
                                    class="w-full bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded text-sm">
                                    Upload General Category Image
                                </button>
                            </div>
                        </form>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-500">No general categories found. Please create categories first.</p>
                @endif
            </div>

            <!-- Individual Category Images Section -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Individual Category Images</h3>

                @if($categories->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($categories as $category)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3">{{ $category->name }}</h4>

                        @if(isset($categoryImages[$category->id]))
                        <div class="mb-3">
                            <img src="data:image/jpeg;base64,{{ base64_encode($categoryImages[$category->id]->image) }}"
                                alt="{{ $category->name }}"
                                class="w-full h-32 object-cover rounded-md shadow-sm">
                            <div class="mt-2">
                                <button onclick="deleteImage({{ $categoryImages[$category->id]->id }})"
                                    class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs">
                                    Delete
                                </button>
                            </div>
                        </div>
                        @endif

                        <form class="categoryImageForm" data-category-id="{{ $category->id }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="category_id" value="{{ $category->id }}">
                            <div class="space-y-2">
                                <input type="file"
                                    name="category_image"
                                    accept="image/*"
                                    class="block w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <button type="submit"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                                    Upload Category Image
                                </button>
                            </div>
                        </form>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-500">No categories found. Please create categories first.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div id="loadingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl">
        <div class="flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="text-gray-700">Uploading image...</span>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Hero Banner Upload Routes
        const heroBannerUploadRoute = "{{ route('admin.shopfront.images.upload-hero-banner') }}";
        const categoryImageUploadRoute = "{{ route('admin.shopfront.images.upload-category-image') }}";
        const generalCategoryImageUploadRoute = "{{ route('admin.shopfront.images.upload-general-category-image') }}";
        const deleteImageRoute = "{{ route('admin.shopfront.images.delete') }}";

        // Hero Banner Upload
        document.getElementById('heroBannerForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const fileInput = document.getElementById('hero_banner');

            if (!fileInput.files[0]) {
                alert('Please select an image file');
                return;
            }

            showLoading();

            fetch(heroBannerUploadRoute, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showNotification('Hero banner uploaded successfully!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification(data.message || 'Upload failed', 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    showNotification('An error occurred during upload', 'error');
                });
        });

        // Category Image Upload
        document.querySelectorAll('.categoryImageForm').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const fileInput = this.querySelector('input[type="file"]');

                if (!fileInput.files[0]) {
                    alert('Please select an image file');
                    return;
                }

                showLoading();

                fetch(categoryImageUploadRoute, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        hideLoading();
                        if (data.success) {
                            showNotification('Category image uploaded successfully!', 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showNotification(data.message || 'Upload failed', 'error');
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        console.error('Error:', error);
                        showNotification('An error occurred during upload', 'error');
                    });
            });
        });

        // General Category Image Upload
        document.querySelectorAll('.generalCategoryImageForm').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const fileInput = this.querySelector('input[type="file"]');

                if (!fileInput.files[0]) {
                    alert('Please select an image file');
                    return;
                }

                showLoading();

                fetch(generalCategoryImageUploadRoute, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        hideLoading();
                        if (data.success) {
                            showNotification('General category image uploaded successfully!', 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showNotification(data.message || 'Upload failed', 'error');
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        console.error('Error:', error);
                        showNotification('An error occurred during upload', 'error');
                    });
            });
        });
    });

    function deleteImage(imageId) {
        if (!confirm('Are you sure you want to delete this image?')) {
            return;
        }

        showLoading();

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const deleteImageRoute = "{{ route('admin.shopfront.images.delete') }}";

        fetch(deleteImageRoute, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    image_id: imageId
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification('Image deleted successfully!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification(data.message || 'Delete failed', 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('An error occurred during deletion', 'error');
            });
    }

    function showLoading() {
        document.getElementById('loadingModal').classList.remove('hidden');
        document.getElementById('loadingModal').classList.add('flex');
    }

    function hideLoading() {
        document.getElementById('loadingModal').classList.add('hidden');
        document.getElementById('loadingModal').classList.remove('flex');
    }

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 ' +
            (type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white');
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
</script>
@endsection