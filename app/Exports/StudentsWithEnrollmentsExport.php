<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsWithEnrollmentsExport implements FromCollection, WithHeadings
{
    protected $classId;
    protected $sessionId;

    public function __construct($classId = null, $sessionId = null)
    {
        $this->classId = $classId;
        $this->sessionId = $sessionId;
    }

    public function collection()
    {
        $students = Student::query()
            ->with(['enrollments' => function ($query) {
                $query->with(['session', 'schoolClass']);
            }])
            ->when(!is_null($this->classId), function ($query) {
                $query->whereHas('enrollments', function ($q) {
                    $q->where('school_class_id', $this->classId);
                });
            })
            ->when(!is_null($this->sessionId), function ($query) {
                $query->whereHas('enrollments', function ($q) {
                    $q->where('session_id', $this->sessionId);
                });
            })
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get();

        return $students->map(function ($student) {
            $enrollment = $student->enrollments->first();
            
            return [
                $enrollment ? $enrollment->id : '',
                $student->admission_number,
                $student->surname,
                $student->first_name,
                $student->middle_name,
                trim($student->surname . ' ' . $student->first_name . ' ' . $student->middle_name),
                $student->gender ?? '',
                $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '',
                $student->phone_number ?? '',
                $enrollment && $enrollment->schoolClass ? $enrollment->schoolClass->name : '',
                $enrollment && $enrollment->session ? $enrollment->session->name : '',
                $enrollment ? $enrollment->status : '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Enrollment ID',
            'Admission Number',
            'Surname',
            'First Name',
            'Middle Name',
            'Full Name',
            'Gender',
            'Date of Birth',
            'Phone Number',
            'Class',
            'Session',
            'Enrollment Status',
        ];
    }
}
