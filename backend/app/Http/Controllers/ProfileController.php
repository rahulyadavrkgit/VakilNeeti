<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index()
    {
        $firm = DB::table('firm_profiles')
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->first();

        return view('profile.index', compact('firm'));
    }
}