<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GradeBoundaryController extends Controller
{
    public function index()
    {
        return GradeBoundary::orderByDesc('min_score')->get();
    }

    public function store(Request $request)
    {
        return GradeBoundary::create($request->all());
    }
}
