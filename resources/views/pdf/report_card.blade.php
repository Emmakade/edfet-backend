@php
if (!function_exists('ordinal')) {
    function ordinal($number) {
        $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
        if (($number % 100) >= 11 && ($number % 100) <= 13)
            return $number . 'th';
        return $number . $ends[$number % 10];
    }
}
@endphp

<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: DejaVu Sans; font-size: 11px; }
        table { width:100%; border-collapse: collapse; }
        th, td { padding:6px; border-bottom:1px solid #eee; }
        th { border-bottom:2px solid #000; font-size:10px; }
        .text-center { text-align:center; }
        .text-right { text-align:right; }
    </style>
</head>

<body>

<h2 style="text-align:center;">STUDENT REPORT CARD</h2>

<!-- STUDENT INFO -->
<table>
    <tr>
        <td><strong>Name:</strong> {{ $student->full_name }}</td>
        <td><strong>Admission No:</strong> {{ $student->admission_number }}</td>
        <td><strong>Gender:</strong> {{ $student->gender }}</td>
    </tr>
    <tr>
        <td><strong>Class:</strong> {{ $enrollment->schoolClass->name }}</td>
        <td><strong>Session:</strong> {{ $enrollment->session->name ?? '' }}</td>
        <td><strong>Term:</strong> {{ $result->term_id ?? '' }}</td>
    </tr>
</table>

<br>

<!-- ACADEMIC TABLE -->
<table>
    <thead>
        <tr>
            <th>Subject</th>
            @if(isset($subjects[0]))
                @foreach($subjects[0]->assessments as $assessment)
                    <th class="text-center">{{ $assessment->name }}</th>
                @endforeach
            @endif
            <th>Total</th>
            <th>Grade</th>
            <th>Pos</th>
            <th>Avg</th>
        </tr>
    </thead>

    <tbody>
        @foreach($subjects as $subject)
        <tr>
            <td>{{ $subject->subject->name }}</td>

            @foreach($subject->assessments as $assessment)
                <td class="text-center">{{ $assessment->score }}</td>
            @endforeach

            <td class="text-center"><strong>{{ $subject->total }}</strong></td>
            <td class="text-center">{{ $subject->grade }}</td>
            <td class="text-center">{{ $subject->subject_position }}</td>
            <td class="text-right">{{ $subject->class_average }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<br>

<!-- SUMMARY -->
<table>
    <tr>
        <td>Total Score:</td>
        <td>{{ $result->total_score ?? 0 }}</td>
    </tr>
    <tr>
        <td>Average:</td>
        <td>{{ $result->average_score ?? 0 }}</td>
    </tr>
    <tr>
        <td>Position:</td>
        <td>{{ $result ? ordinal($result->overall_position) : '-' }}</td>
    </tr>
</table>

<br>

<!-- REMARKS -->
<p><strong>Class Teacher:</strong> {{ $remark->teacher_remark ?? '' }}</p>
<p><strong>Head Teacher:</strong> {{ $remark->principal_remark ?? '' }}</p>

</body>
</html>