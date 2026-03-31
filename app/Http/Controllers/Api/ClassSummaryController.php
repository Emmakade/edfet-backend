<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassSummary;
use Illuminate\Http\Request;

class ClassSummaryController extends Controller
{
    public function index()
    {
        $summaries = ClassSummary::with('schoolClass', 'term', 'session', 'subject')->get();
        return response()->json($summaries);
    }

    public function show($class_id, $term_id, $session_id)
    {
        $summary = ClassSummary::where('school_class_id', $class_id)
                               ->where('term_id', $term_id)
                               ->where('session_id', $session_id)
                               ->with('schoolClass', 'term', 'session', 'subject')
                               ->first();

        if (!$summary) {
            return response()->json(['message' => 'Class summary not found'], 404);
        }

        return response()->json($summary);
    }
}