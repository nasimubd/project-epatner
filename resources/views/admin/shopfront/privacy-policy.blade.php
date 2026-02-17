<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-4xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
                    <h1 class="text-2xl font-bold text-gray-800">Privacy Policy</h1>
                </div>
                <a href="javascript:history.back()" class="text-blue-600 hover:text-blue-800 flex items-center space-x-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Shop</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-sm p-8">
            <!-- Last Updated -->
            <div class="mb-8 p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                <p class="text-sm text-blue-700">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Last updated: {{ date('F d, Y') }}
                </p>
            </div>

            <!-- Introduction -->
            <section class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-info-circle text-blue-600 mr-3"></i>
                    Introduction
                </h2>
                <p class="text-gray-600 leading-relaxed">
                    Welcome to ePatner 3.0. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our shopfront platform and make purchases through our services. Please read this privacy policy carefully. If you do not agree with the terms of this privacy policy, please do not access the site.
                </p>
            </section>

            <!-- Information We Collect -->
            <section class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-database text-green-600 mr-3"></i>
                    Information We Collect
                </h2>

                <div class="space-y-6">
                    <div class="border-l-4 border-green-500 pl-4">
                        <h3 class="text-lg font-medium text-gray-800 mb-2">Personal Information</h3>
                        <ul class="text-gray-600 space-y-1">
                            <li>• Name and contact information</li>
                            <li>• Email address and phone number</li>
                            <li>• Billing and shipping addresses</li>
                            <li>• Payment information (processed securely)</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-green-500 pl-4">
                        <h3 class="text-lg font-medium text-gray-800 mb-2">Order Information</h3>
                        <ul class="text-gray-600 space-y-1">
                            <li>• Products purchased and quantities</li>
                            <li>• Order history and preferences</li>
                            <li>• Transaction details</li>
                        </ul>
                    </div>

                    <div class="border-l-4 border-green-500 pl-4">
                        <h3 class="text-lg font-medium text-gray-800 mb-2">Technical Information</h3>
                        <ul class="text-gray-600 space-y-1">
                            <li>• IP address and browser information</li>
                            <li>• Device information and operating system</li>
                            <li>• Usage data and site interactions</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- How We Use Information -->
            <section class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-cogs text-purple-600 mr-3"></i>
                    How We Use Your Information
                </h2>

                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <h3 class="font-medium text-gray-800 mb-2">Order Processing</h3>
                        <p class="text-sm text-gray-600">Process and fulfill your orders, manage payments, and provide customer support.</p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <h3 class="font-medium text-gray-800 mb-2">Communication</h3>
                        <p class="text-sm text-gray-600">Send order confirmations, updates, and important service notifications.</p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <h3 class="font-medium text-gray-800 mb-2">Service Improvement</h3>
                        <p class="text-sm text-gray-600">Analyze usage patterns to improve our platform and user experience.</p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <h3 class="font-medium text-gray-800 mb-2">Legal Compliance</h3>
                        <p class="text-sm text-gray-600">Comply with applicable laws and protect our legal rights.</p>
                    </div>
                </div>
            </section>

            <!-- Information Sharing -->
            <section class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-share-alt text-orange-600 mr-3"></i>
                    Information Sharing
                </h2>

                <div class="bg-orange-50 p-6 rounded-lg">
                    <p class="text-gray-700 mb-4">We do not sell, trade, or rent your personal information to third parties. We may share your information only in the following circumstances:</p>
                    <ul class="text-gray-600 space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-check text-orange-600 mr-2 mt-1"></i>
                            <span>With service providers who assist in order fulfillment and payment processing</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-orange-600 mr-2 mt-1"></i>
                            <span>When required by law or to protect our legal rights</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-orange-600 mr-2 mt-1"></i>
                            <span>In connection with a business transfer or merger</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-orange-600 mr-2 mt-1"></i>
                            <span>With your explicit consent</span>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Data Security -->
            <section class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-lock text-red-600 mr-3"></i>
                    Data Security
                </h2>

                <div class="bg-red-50 p-6 rounded-lg">
                    <p class="text-gray-700 mb-4">We implement appropriate security measures to protect your personal information:</p>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-shield-alt text-red-600"></i>
                            <span class="text-gray-600">SSL encryption for data transmission</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-server text-red-600"></i>
                            <span class="text-gray-600">Secure server infrastructure</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-key text-red-600"></i>
                            <span class="text-gray-600">Access controls and authentication</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-eye text-red-600"></i>
                            <span class="text-gray-600">Regular security monitoring</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Your Rights -->
            <section class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user-shield text-indigo-600 mr-3"></i>
                    Your Rights
                </h2>

                <div class="space-y-4">
                    <div class="flex items-start space-x-3 p-4 bg-indigo-50 rounded-lg">
                        <i class="fas fa-eye text-indigo-600 mt-1"></i>
                        <div>
                            <h3 class="font-medium text-gray-800">Access</h3>
                            <p class="text-sm text-gray-600">Request access to your personal information we hold</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3 p-4 bg-indigo-50 rounded-lg">
                        <i class="fas fa-edit text-indigo-600 mt-1"></i>
                        <div>
                            <h3 class="font-medium text-gray-800">Correction</h3>
                            <p class="text-sm text-gray-600">Request correction of inaccurate information</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3 p-4 bg-indigo-50 rounded-lg">
                        <i class="fas fa-trash text-indigo-600 mt-1"></i>
                        <div>
                            <h3 class="font-medium text-gray-800">Deletion</h3>
                            <p class="text-sm text-gray-600">Request deletion of your personal information</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Cookies -->
            <section class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-cookie-bite text-yellow-600 mr-3"></i>
                    Cookies and Tracking
                </h2>

                <div class="bg-yellow-50 p-6 rounded-lg">
                    <p class="text-gray-700 mb-4">We use cookies and similar technologies to:</p>
                    <ul class="text-gray-600 space-y-2">
                        <li>• Remember your preferences and shopping cart</li>
                        <li>• Analyze site usage and improve performance</li>
                        <li>• Provide personalized content and recommendations</li>
                        <li>• Ensure security and prevent fraud</li>
                    </ul>
                    <p class="text-sm text-gray-600 mt-4">You can control cookie settings through your browser preferences.</p>
                </div>
            </section>

            <!-- Contact Information -->
            <section class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-envelope text-blue-600 mr-3"></i>
                    Contact Us
                </h2>

                <div class="bg-blue-50 p-6 rounded-lg">
                    <p class="text-gray-700 mb-4">If you have questions about this Privacy Policy or our data practices, please contact us:</p>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-envelope text-blue-600"></i>
                            <span class="text-gray-600">support@epatner.com</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-phone text-blue-600"></i>
                            <span class="text-gray-600">+880-XXX-XXXXXX</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-map-marker-alt text-blue-600"></i>
                            <span class="text-gray-600">Dhaka, Bangladesh</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Changes to Policy -->
            <section class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-sync-alt text-gray-600 mr-3"></i>
                    Changes to This Policy
                </h2>

                <div class="bg-gray-50 p-6 rounded-lg border">
                    <p class="text-gray-700">
                        We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date. We encourage you to review this Privacy Policy periodically for any changes.
                    </p>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-12">
        <div class="max-w-4xl mx-auto px-4 py-6">
            <div class="text-center text-gray-500 text-sm">
                <p>&copy; {{ date('Y') }} ePatner 3.0. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>

</html>