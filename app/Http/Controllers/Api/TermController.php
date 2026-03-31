<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Term;
use Illuminate\Http\Request;

class TermController extends Controller
{
    public function index()
    {
        $terms = Term::with('session')->get();
        return response()->json($terms);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'session_id' => 'required|exists:sessions,id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date'
        ]);

        $term = Term::create($data);
        return response()->json($term, 201);
    }

    public function show(Term $term)
    {
        return response()->json($term->load('session'));
    }

    public function update(Request $request, Term $term)
    {
        $data = $request->validate([
            'name' => 'string|max:255',
            'session_id' => 'exists:sessions,id',
            'starts_at' => 'date',
            'ends_at' => 'date'
        ]);

        $term->update($data);
        return response()->json($term);
    }

    public function destroy(Term $term)
    {
        $term->delete();
        return response()->json(['message' => 'Term deleted']);
    }

    public function current()
    {
        $term = Term::where('starts_at', '<=', now())
                    ->where('ends_at', '>=', now())
                    ->first();
        return response()->json($term);
    }
}