<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Score;
use App\Models\School;
use App\Models\SessionModel;
use App\Models\Term;
use App\Models\StudentResult;
use App\Models\SchoolClass;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportCardController extends Controller
{

    public function generate(Request $request, $studentId)
    {
        $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'term_id' => 'required|exists:terms,id',
            'session_id' => 'required|exists:sessions,id',
        ]);
       
        $student = Student::with(['schoolClass', 'user'])->findOrFail($studentId);
        $school = School::first();
        $class = SchoolClass::findOrFail($request->school_class_id);
        $term = Term::findOrFail($request->term_id);
        $session = SessionModel::findOrFail($request->session_id);

        if ($term->session_id !== $session->id) {
            return response()->json(['message' => 'Term does not belong to the selected session'], 422);
        }
     
        $scores = Score::with('subject:id,name')
            ->where('student_id', $studentId)
            ->where('school_class_id', $request->school_class_id)
            ->where('term_id', $request->term_id)
            ->where('session_id', $request->session_id)
            ->get();

        $result = StudentResult::where('student_id', $studentId)
            ->where('school_class_id', $request->school_class_id)
            ->where('term_id', $request->term_id)
            ->where('session_id', $request->session_id)
            ->first();
        $logo = base64_encode(file_get_contents(public_path('images/logo.png')));
        $pdf = Pdf::loadView('pdf.report_card', [
            'student' => $student,
            'scores' => $scores,
            'result' => $result,
            'school' => $school,
            'class' => $class,
            'session' => $session->name,
            'term' => $term->name,
            'attendance' => [
                'opened' => 75,
                'present' => 70,
            ],
            'teacher_remark' => 'Keep improving!',
            'head_remark' => 'Excellent progress.',
            'logo' => public_path('images/logo.png'),
            'next_term' => '10th May 2026',
        ]);
       
        return $pdf->download('report-card.pdf');
    }

    public function generateClass(Request $request)
    {
        $request->validate([
            'classId' => 'required|exists:school_classes,id',
            'termId' => 'required|exists:terms,id',
            'sessionId' => 'required|exists:sessions,id',
        ]);

        $school = School::first();
        $term = Term::findOrFail($request->termId);
        $session = SessionModel::findOrFail($request->sessionId);

        if ($term->session_id !== $session->id) {
            return response()->json(['message' => 'Term does not belong to the selected session'], 422);
        }

        $students = Student::with(['schoolClass', 'user'])
            ->where('school_class_id', $request->classId)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        if ($students->isEmpty()) {
            abort(404, 'No students found for this class.');
        }

        $scoresByStudent = Score::with('subject')
            ->whereIn('student_id', $students->pluck('id'))
            ->where('school_class_id', $request->classId)
            ->where('term_id', $request->termId)
            ->where('session_id', $request->sessionId)
            ->get()
            ->map(function ($score) {
                $score->ca = $score->ca_score;
                $score->exam = $score->exam_score;

                return $score;
            })
            ->groupBy('student_id');

        $html = $students->map(function ($student) use ($school, $scoresByStudent, $request) {
            $student->name = trim(
                collect([
                    $student->first_name,
                    $student->last_name,
                ])->filter()->implode(' ')
            ) ?: optional($student->user)->name ?: 'Unknown Student';

            $student->class = $student->schoolClass;

            return view('pdf.report_card', [
                'school' => $school,
                'student' => $student,
                'scores' => $scoresByStudent->get($student->id, collect()),
                'class' => $student->schoolClass,
                'term' => $term->name,
                'session' => $session->name,
                'attendance' => [
                    'opened' => 75,
                    'present' => 70,
                ],
                'teacher_remark' => 'Keep improving!',
                'head_remark' => 'Excellent progress.',
                'logo' => public_path('images/logo.png'),
                'next_term' => '10th May 2026',
            ])->render();
        })->implode('<div style="page-break-after: always;"></div>');

        $pdf = Pdf::loadHTML($html);

        return $pdf->download("report_cards_class_{$request->classId}.pdf");
    }
}
