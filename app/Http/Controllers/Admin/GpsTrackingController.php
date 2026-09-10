<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GpsTrackingController extends Controller
{
    /**
     * Display the GPS tracking map for admin (direct integration without iframe)
     */
    public function index()
    {
        return view('admin.gps-tracking-direct');
    }
}
