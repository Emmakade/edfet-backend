<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\ClassStoreRequest;
use App\Models\SchoolClass;

class ClassController extends Controller
{
    public function index()
    {
        $classes = SchoolClass::with([
            'classTeacher',
            'classSubjects.subject',
            'classSubjects.teacher',
            'students',
        ])->get();

        return response()->json($classes);
    }

    public function store(ClassStoreRequest $request)
    {
        $data = $request->validated();
        $c = SchoolClass::create($data);
        return response()->json($c, 201);
    }

    public function show(SchoolClass $school_class)
    {
        $school_class->load([
            'classTeacher',
            'classSubjects.subject',
            'classSubjects.teacher',
            'students',
        ]);

        return response()->json($school_class);
    }

    public function update(ClassStoreRequest $request, SchoolClass $school_class)
    {
        $school_class->update($request->validated());
        return response()->json($school_class);
    }

    public function destroy(SchoolClass $school_class)
    {
        $school_class->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function active()
    {
        return $this->index();
    }
}
