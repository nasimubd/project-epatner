<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessAdmin;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'businesses' => Business::count(),
            'admins' => BusinessAdmin::count(),
            'users' => User::count(),
        ];

        return view('super-admin.dashboard', compact('stats'));
    }
}
