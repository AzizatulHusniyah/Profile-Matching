<?php

namespace App\Http\Controllers;

use App\Models\SpkResult;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function index(): View
    {
        $result = SpkResult::where('user_id', auth()->id())->first();
        return view('recommendations.index', ['result' => $result]);
    }
}
