<?php

return [
    // Path to your payment partner's QR code image
    'qr_code_image' => 'images/payment-qr-code.png', // Store your QR code in public/images/

    // Payment partner details
    'payment_partner' => [
        'name' => 'Your Payment Partner Name',
        'upi_id' => 'your-partner-upi@bank', // If needed for reference
        'merchant_id' => 'your-merchant-id', // If provided by partner
    ],

    // Payment instructions
    'instructions' => [
        'Please scan the QR code with any UPI app',
        'Enter the exact amount shown',
        'Add the transaction ID in remarks/description',
        'Complete the payment',
        'Click "I have paid" button'
    ]
];
