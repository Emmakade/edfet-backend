<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\SubjectStoreRequest;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        return response()->json(Subject::all());
    }

    public function store(SubjectStoreRequest $request)
    {
        $data = $request->validated();
        $subject = Subject::create($data);
        return response()->json($subject, 201);
    }

    public function update(SubjectStoreRequest $request, Subject $subject)
    {
        $subject->update($request->validated());
        return response()->json($subject);
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();
        return response()->json(['message'=>'Deleted']);
    }
}
