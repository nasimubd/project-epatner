const CACHE_NAME = "shopfront-cache-v1";
const urlsToCache = [
    "/",
    "/css/app.css",
    "/js/app.js",
    "https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css",
    "https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js",
    "/images/default-product.jpeg",
];

// Install event - cache assets
self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log("Opened cache");
            return cache.addAll(urlsToCache);
        })
    );
});

// Fetch event - serve from cache if available
self.addEventListener("fetch", (event) => {
    event.respondWith(
        caches.match(event.request).then((response) => {
            // Cache hit - return response
            if (response) {
                return response;
            }

            // Clone the request because it's a one-time use stream
            const fetchRequest = event.request.clone();

            return fetch(fetchRequest).then((response) => {
                // Check if we received a valid response
                if (
                    !response ||
                    response.status !== 200 ||
                    response.type !== "basic"
                ) {
                    return response;
                }

                // Clone the response because it's a one-time use stream
                const responseToCache = response.clone();

                caches.open(CACHE_NAME).then((cache) => {
                    // Don't cache API requests or large base64 images
                    if (
                        !event.request.url.includes("/api/") &&
                        !event.request.url.includes("data:image")
                    ) {
                        cache.put(event.request, responseToCache);
                    }
                });

                return response;
            });
        })
    );
});

// Activate event - clean up old caches
self.addEventListener("activate", (event) => {
    const cacheWhitelist = [CACHE_NAME];

    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        // Delete any old caches that aren't in our whitelist
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Handle messages from the main thread
self.addEventListener("message", (event) => {
    if (event.data.action === "skipWaiting") {
        self.skipWaiting();
    }

    // Handle cache updates
    if (event.data.action === "clearProductCache") {
        const shopfrontId = event.data.shopfrontId;

        caches.open(CACHE_NAME).then((cache) => {
            cache.keys().then((requests) => {
                requests.forEach((request) => {
                    if (request.url.includes(`shopfront/${shopfrontId}`)) {
                        cache.delete(request);
                    }
                });
            });
        });
    }
});
