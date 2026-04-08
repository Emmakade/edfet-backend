<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\SubjectStoreRequest;
use App\Models\ClassSubject;
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
        return response()->json(['message' => 'Deleted']);
    }

    public function getByClass(Request $request, $classId)
    {
        $subjects = ClassSubject::query()
            ->with(['subject', 'teacher'])
            ->where('school_class_id', $classId)
            ->when($request->filled('session_id'), function ($query) use ($request) {
                $query->where('session_id', $request->integer('session_id'));
            })
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $subjects,
            'count' => $subjects->count(),
        ]);
    }
}
