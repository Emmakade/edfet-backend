<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SessionModel;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index()
    {
        $sessions = SessionModel::all();
        return response()->json($sessions);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'year_start' => 'required|date',
            'year_end' => 'required|date',
            'active' => 'boolean'
        ]);

        $session = SessionModel::create($data);
        return response()->json($session, 201);
    }

    public function show(SessionModel $session)
    {
        return response()->json($session->load('terms'));
    }

    public function update(Request $request, SessionModel $session)
    {
        $data = $request->validate([
            'name' => 'string|max:255',
            'year_start' => 'date',
            'year_end' => 'date',
            'active' => 'boolean'
        ]);

        $session->update($data);
        return response()->json($session);
    }

    public function destroy(SessionModel $session)
    {
        $session->delete();
        return response()->json(['message' => 'Session deleted']);
    }

    public function current()
    {
        $session = SessionModel::where('active', true)->first();
        return response()->json($session);
    }
}