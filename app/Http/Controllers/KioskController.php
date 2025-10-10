<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KioskController extends Controller
{
    /**
     * Display the kiosk leaderboard view
     */
    public function leaderboard()
    {
        return view('kiosk.leaderboard');
    }

    /**
     * Display the kiosk map view with team locations
     */
    public function map()
    {
        return view('kiosk.map');
    }

    /**
     * Display the LED kiosk view with both leaderboard and map
     */
    public function led()
    {
        return view('kiosk.led');
    }
}