import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/js/admin/inventory/products/create.js",
                "resources/js/admin/inventory/products/product-index.js",
                "resources/js/admin/inventory-transactions.js",
                "resources/js/admin/dashboard/dashboard-sales-breakdown.js",
                "resources/js/admin/sw-shopfront.js",
                "resources/js/admin/shopfront-images.js",
                "resources/js/admin/invoices-index.js",
            ],
            refresh: true,
        }),
    ],
});
