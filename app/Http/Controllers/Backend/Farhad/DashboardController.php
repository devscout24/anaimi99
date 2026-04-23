<?php

namespace App\Http\Controllers\Backend\Farhad;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();

        return view('backend.layouts.dashboard.index', compact('totalUsers'));
    }
}
