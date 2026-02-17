<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->business) {
            $business = $user->business;

            // Check if subscription is expired
            if (
                $business->subscription_status === 'expired' ||
                ($business->subscription_expires_at && now()->isAfter($business->subscription_expires_at))
            ) {

                // Update business status
                $business->update([
                    'subscription_active' => false,
                    'subscription_status' => 'expired'
                ]);

                // Redirect to payment page
                return redirect()->route('subscription.payment');
            }
        }

        return $next($request);
    }
}
