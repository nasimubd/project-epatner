// Get configuration from the script tag
const appConfig = JSON.parse(document.getElementById("app-config").textContent);
const routes = appConfig.routes;
const csrfToken = appConfig.csrfToken;

document.addEventListener("DOMContentLoaded", function () {
    // Hero Banner Upload
    const heroBannerForm = document.getElementById("heroBannerForm");
    if (heroBannerForm) {
        heroBannerForm.addEventListener("submit", function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const fileInput = document.getElementById("hero_banner");

            if (!fileInput.files[0]) {
                showNotification("Please select an image file", "error");
                return;
            }

            showLoading(true);

            fetch(routes.uploadHeroBanner, {
                method: "POST",
                body: formData,
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    showLoading(false);
                    if (data.success) {
                        showNotification(data.message, "success");
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification(data.message, "error");
                    }
                })
                .catch((error) => {
                    showLoading(false);
                    console.error("Error:", error);
                    showNotification(
                        "An error occurred while uploading",
                        "error"
                    );
                });
        });
    }

    // Category Image Upload
    const categoryForms = document.querySelectorAll(".categoryImageForm");
    categoryForms.forEach((form) => {
        form.addEventListener("submit", function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const fileInput = this.querySelector('input[type="file"]');

            if (!fileInput.files[0]) {
                showNotification("Please select an image file", "error");
                return;
            }

            showLoading(true);

            fetch(routes.uploadCategoryImage, {
                method: "POST",
                body: formData,
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    showLoading(false);
                    if (data.success) {
                        showNotification(data.message, "success");
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification(data.message, "error");
                    }
                })
                .catch((error) => {
                    showLoading(false);
                    console.error("Error:", error);
                    showNotification(
                        "An error occurred while uploading",
                        "error"
                    );
                });
        });
    });

    // Delete Image Event Listeners
    const deleteButtons = document.querySelectorAll(".delete-image-btn");
    deleteButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const imageId = this.getAttribute("data-image-id");
            deleteImageById(imageId);
        });
    });
});

// Delete image function
function deleteImageById(imageId) {
    if (!confirm("Are you sure you want to delete this image?")) {
        return;
    }

    showLoading(true);

    fetch(routes.deleteImage, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
        },
        body: JSON.stringify({
            image_id: imageId,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            showLoading(false);
            if (data.success) {
                showNotification(data.message, "success");
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification(data.message, "error");
            }
        })
        .catch((error) => {
            showLoading(false);
            console.error("Error:", error);
            showNotification("An error occurred while deleting", "error");
        });
}

// Utility functions
function showLoading(show) {
    const modal = document.getElementById("loadingModal");
    if (modal) {
        if (show) {
            modal.classList.remove("hidden");
        } else {
            modal.classList.add("hidden");
        }
    }
}

function showNotification(message, type) {
    type = type || "info";

    const notification = document.createElement("div");
    const bgColorMap = {
        success: "bg-green-500",
        error: "bg-red-500",
        warning: "bg-yellow-500",
        info: "bg-blue-500",
    };

    const bgColor = bgColorMap[type] || "bg-blue-500";

    notification.className =
        "fixed top-4 right-4 " +
        bgColor +
        " text-white px-6 py-4 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300";

    const iconMap = {
        success:
            '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>',
        error: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>',
        warning:
            '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>',
        info: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>',
    };

    const icon = iconMap[type] || iconMap.info;

    notification.innerHTML =
        '<div class="flex items-center space-x-3">' +
        '<div class="flex-shrink-0">' +
        icon +
        "</div>" +
        '<p class="font-medium">' +
        message +
        "</p>" +
        "</div>";

    document.body.appendChild(notification);

    // Slide in animation
    setTimeout(() => {
        notification.style.transform = "translateX(0)";
    }, 100);

    // Slide out and remove
    setTimeout(() => {
        notification.style.transform = "translateX(100%)";
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}
