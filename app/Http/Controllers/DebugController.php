<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;

class DebugController extends Controller
{
    public function index()
    {
        return view('debug');
    }
}
