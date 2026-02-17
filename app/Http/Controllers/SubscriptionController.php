<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\SubscriptionPlan;
use App\Models\BusinessSubscription;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function showPaymentPage()
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();
        $business = Auth::user()->business;

        // Get the QR code from config or database
        $qrCodeImage = config('payment.qr_code_image'); // Store QR code path in config

        return view('subscription.payment', compact('plans', 'business', 'qrCodeImage'));
    }

    public function initiatePayment(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id'
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        $business = Auth::user()->business;

        // Create payment transaction
        $transaction = PaymentTransaction::create([
            'business_id' => $business->id,
            'subscription_plan_id' => $plan->id,
            'transaction_id' => 'TXN_' . Str::random(10) . time(),
            'amount' => $plan->price,
            'payment_method' => 'QR_CODE',
            'status' => 'pending'
        ]);

        // Get your payment partner's QR code
        $qrCodeImage = config('payment.qr_code_image');

        return response()->json([
            'success' => true,
            'transaction_id' => $transaction->transaction_id,
            'amount' => $plan->price,
            'qr_code_image' => asset($qrCodeImage), // Return your QR code image path
            'payment_instructions' => $this->getPaymentInstructions($transaction)
        ]);
    }

    private function getPaymentInstructions($transaction)
    {
        return [
            'amount' => $transaction->amount,
            'transaction_id' => $transaction->transaction_id,
            'business_name' => $transaction->business->name,
            'instructions' => [
                '1. Scan the QR code with any UPI app',
                '2. Enter amount: ₹' . $transaction->amount,
                '3. Add transaction reference: ' . $transaction->transaction_id,
                '4. Complete the payment',
                '5. Click "I have paid" button below'
            ]
        ];
    }

    public function markPaymentDone(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:payment_transactions,transaction_id',
            'payment_reference' => 'required|string' // User enters their payment reference
        ]);

        $transaction = PaymentTransaction::where('transaction_id', $request->transaction_id)->first();

        // Store the payment reference provided by user
        $transaction->update([
            'payment_details' => [
                'user_payment_reference' => $request->payment_reference,
                'marked_paid_at' => now(),
                'user_ip' => $request->ip()
            ]
        ]);

        // You can either auto-activate or keep it pending for manual verification
        // Option 1: Auto-activate (if you trust the system)
        $this->activateSubscription($transaction);

        // Option 2: Keep pending for manual verification
        // $transaction->update(['status' => 'verification_pending']);

        return response()->json([
            'success' => true,
            'message' => 'Payment marked as completed. Your subscription is now active!'
        ]);
    }

    public function verifyPayment(Request $request)
    {
        // This endpoint can be called by your payment partner's webhook
        // or used for manual verification by admin

        $request->validate([
            'transaction_id' => 'required',
            'payment_status' => 'required|in:success,failed',
            'payment_reference' => 'required'
        ]);

        $transaction = PaymentTransaction::where('transaction_id', $request->transaction_id)->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found']);
        }

        if ($request->payment_status === 'success') {
            $transaction->update([
                'status' => 'completed',
                'payment_details' => array_merge(
                    $transaction->payment_details ?? [],
                    [
                        'verified_payment_reference' => $request->payment_reference,
                        'verified_at' => now()
                    ]
                )
            ]);

            $this->activateSubscription($transaction);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified and subscription activated'
            ]);
        } else {
            $transaction->update(['status' => 'failed']);
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed'
            ]);
        }
    }

    private function activateSubscription($transaction)
    {
        $business = $transaction->business;
        $plan = $transaction->subscriptionPlan;

        // Update transaction status
        $transaction->update(['status' => 'completed']);

        // Calculate subscription dates
        $startsAt = Carbon::now();
        $expiresAt = $startsAt->copy()->addDays($plan->duration_days);

        // Create or update business subscription
        BusinessSubscription::create([
            'business_id' => $business->id,
            'subscription_plan_id' => $plan->id,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'status' => 'active'
        ]);

        // Update business subscription status
        $business->update([
            'subscription_active' => true,
            'subscription_expires_at' => $expiresAt,
            'subscription_status' => 'active'
        ]);
    }
}
