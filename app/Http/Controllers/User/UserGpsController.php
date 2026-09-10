<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserGpsController extends Controller
{
    /**
     * Display user's GPS tracking page (self-location only)
     */
    public function index()
    {
        $user = Auth::user();

        return view('user.gps-tracking', compact('user'));
    }
}
