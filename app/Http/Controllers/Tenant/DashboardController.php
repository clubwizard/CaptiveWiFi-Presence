<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the tenant dashboard.
     */
    public function index(): View
    {
        return view('tenant.dashboard');
    }
}
