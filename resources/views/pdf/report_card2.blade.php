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
        *{margin:0; padding:0}
        body {
            font-family: DejaVu Sans;
            font-size: 11px;
            color: #222;
            margin: 20px;
        }

        .container {
            border: 2px solid #000;
            padding: 15px;
        }

        h1, h2, h3, p {
            margin: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .school-name {
            font-size: 18px;
            font-weight: bold;
        }

        .report-title {
            font-size: 14px;
            margin-top: 5px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .info-table {
            width: 100%;
            margin-top: 10px;
        }

        .info-table td {
            padding: 4px;
            border: none;
        }

        .section-title {
            margin-top: 15px;
            font-weight: bold;
            font-size: 12px;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th {
            background: #f0f0f0;
            font-size: 11px;
            padding: 6px;
            border: 1px solid #000;
        }

        td {
            padding: 6px;
            border: 1px solid #000;
            text-align: center;
        }

        .summary {
            margin-top: 10px;
            width: 50%;
        }

        .remarks {
            margin-top: 15px;
        }

        .signature {
            margin-top: 40px;
            width: 100%;
        }

        .signature td {
            border: none;
            text-align: center;
        }

        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <table style="width:100%; border:none;">
                <tr style="border:none;">
                    <td style="border:none; width:80px;">
                        <img src="{{ $logo }}" width="70">
                    </td>
                    <td style="border:none; text-align:center;">
                        <div class="school-name">{{ $school->name }}</div>
                        <p>{{ $school->address }}</p>
                        <p><em>{{ $school->motto }}</em></p>
                        <div class="report-title">STUDENT REPORT CARD</div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="info-table">
            <tr>
                <td><strong>Name:</strong> {{ $student->first_name }} {{ $student->last_name }}</td>
                <td><strong>Adm No:</strong> {{ $student->admission_number }}</td>
                <td><strong>Gender:</strong> {{ $student->gender }}</td>
            </tr>
            <tr>
                <td><strong>Class:</strong> {{ $class->name }}</td>
                <td><strong>Session:</strong> {{ $session }}</td>
                <td><strong>Term:</strong> {{ $term }}</td>
            </tr>
            <tr>
                <td><strong>Days Open:</strong> {{ $attendance['opened'] }}</td>
                <td><strong>Days Present:</strong> {{ $attendance['present'] }}</td>
                <td><strong>Next Term Begins:</strong> {{ $next_term }}</p></td>
            </tr>
        </table>

        <div class="section-title">Academic Performance</div>

        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>CA(40)</th>
                    <th>Exam(60)</th>
                    <th>Total(100)</th>
                    <th>Grade</th>
                    <th>Rmk</th>
                    <th>Pos</th>
                    <th>Class Avg</th>
                    <th>Class Lwst</th>
                    <th>Class Hgst</th>
                </tr>
            </thead>
            <tbody>
                <div style="
                    position: fixed;
                    top: 40%;
                    left: 20%;
                    opacity: 0.05;
                    font-size: 80px;
                    transform: rotate(-30deg);
                ">
                    {{ $school->name }}
                </div>
                @if($subjects->isEmpty())
                    <tr>
                        <td colspan="10">No subjects available</td>
                    </tr>
                @endif
                @foreach($subjects as $subject)
                <tr style="background: {{ $loop->even ? '#fafafa' : '#fff' }}">
                    <td>{{ $subject->subject->name }}</td>
                    <td>{{ $subject->ca_score }}</td>
                    <td>{{ $subject->exam_score }}</td>
                    <td>{{ $subject->total }}</td>
                    <td>{{ $subject->grade }}</td>
                    <td>{{ $subject->remark }}</td>
                    <td>{{ $subject->subject_position }}</td>
                    <td>{{ $subject->class_average }}</td>
                    <td>{{ $subject->class_lowest }}</td>
                    <td>{{ $subject->class_highest }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top:20px;">
            <strong>Grading System:</strong>
            <table>
                <tr>
                    <td>A (70–100)</td>
                    <td>Excellent</td>
                    <td>B (60–69)</td>
                    <td>Very Good</td>
                </tr>
                <tr>
                    <td>C (50–59)</td>
                    <td>Good</td>
                    <td>D (45–49)</td>
                    <td>Pass</td>
                </tr>
                <tr>
                    <td>F (0–44)</td>
                    <td>Fail</td>
                </tr>
            </table>
        </div>

        <br>
        <div class="section-title">Summary</div>

        <table class="summary">
            <tr>
                <td><strong>Total Score</strong></td>
                <td>{{ $result->total_score ?? 0 }}</td>
            </tr>
            <tr>
                <td><strong>Final Average</strong></td>
                <td>{{ $result->average_score ?? 0 }}</td>
            </tr>
            <tr>
                <td><strong>Position</strong></td>
                <td>{{ $result ? ordinal($result->overall_position) : '-' }}</td>
            </tr>
        </table>

        <div class="remarks">
        <div class="section-title">Remarks</div>

            <p><strong>Class Teacher:</strong> {{ $teacher_remark }}</p>
            <p><strong>Head Teacher:</strong> {{ $head_remark }}</p>
        </div>
        <table class="signature">
            <tr>
                <td>
                    __________________________ <br>
                    Class Teacher
                </td>
                <td>
                    __________________________ <br>
                    Head Teacher
                </td>
            </tr>
        </table>
        <div class="footer">
            This report card is computer generated and does not require a signature.
        </div>
    </div>
</body>
</html>