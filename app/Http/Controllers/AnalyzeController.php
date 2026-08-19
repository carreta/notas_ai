<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;

class AnalyzeController extends Controller
{
    public function index()
    {
        return view('analyze', ['models' => config('models')]);
    }
}
