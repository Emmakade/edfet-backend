<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\SchoolConfigRequest;
use App\Models\School;
use Illuminate\Http\Request;

class SchoolConfigController extends Controller
{
    // show current school config (assumes single school)
    public function show()
    {
        $school = School::first();
        return response()->json($school);
    }

    // update school config
    public function update(SchoolConfigRequest $request)
    {
        $data = $request->validated();
        $school = School::first() ?: new School();
        $school->fill($data);
        $school->save();

        return response()->json($school);
    }
}
