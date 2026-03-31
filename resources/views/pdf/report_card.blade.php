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
        body {
            font-family: DejaVu Sans;
            font-size: 11px;
            color: #1a1a1a;
            margin: 25px;
        }

        /* ===== HEADER ===== */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .logo {
            display: table-cell;
            width: 80px;
        }

        .logo img {
            width: 70px;
        }

        .school-info {
            display: table-cell;
            text-align: center;
        }

        .school-name {
            font-size: 20px;
            font-weight: bold;
        }

        .report-title {
            font-size: 13px;
            margin-top: 4px;
            letter-spacing: 1px;
            font-weight: bold;
            color: #444;
        }

        /* ===== STUDENT INFO ===== */
        .info-grid {
            width: 100%;
            margin-top: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .info-grid td {
            padding: 4px 6px;
        }

        .label {
            color: #777;
            font-size: 10px;
        }

        .value {
            font-weight: bold;
        }

        /* ===== SECTION ===== */
        .section-title {
            margin-top: 18px;
            font-weight: bold;
            font-size: 12px;
            color: #333;
        }

        /* ===== TABLE ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th {
            text-align: left;
            font-size: 10px;
            padding: 6px;
            border-bottom: 2px solid #000;
        }

        td {
            padding: 6px;
            font-size: 10px;
            border-bottom: 1px solid #eee;
        }

        tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* ===== SUMMARY ===== */
        .summary {
            width: 40%;
            margin-top: 10px;
        }

        .summary td {
            padding: 5px;
        }

        .summary td:last-child {
            text-align: right;
            font-weight: bold;
        }

        /* ===== REMARKS ===== */
        .remarks {
            margin-top: 15px;
        }

        /* ===== SIGNATURE ===== */
        .signature {
            margin-top: 40px;
            width: 100%;
        }

        .signature td {
            text-align: center;
            padding-top: 30px;
        }

        /* ===== WATERMARK ===== */
        .watermark {
            position: fixed;
            top: 40%;
            left: 20%;
            opacity: 0.04;
            font-size: 90px;
            transform: rotate(-30deg);
        }

        /* ===== FOOTER ===== */
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 9px;
            color: #888;
        }
    </style>
</head>

<body>

<div class="watermark">{{ $school->name }}</div>

<!-- HEADER -->
<div class="header">
    <div class="logo">
        <img src="{{ $logo }}">
    </div>
    <div class="school-info">
        <div class="school-name">{{ $school->name }}</div>
        <div>{{ $school->address }}</div>
        <div><em>{{ $school->motto }}</em></div>
        <div class="report-title">STUDENT REPORT CARD</div>
    </div>
</div>

<!-- STUDENT INFO -->
<table class="info-grid">
    <tr>
        <td><span class="label">Name</span><br><span class="value">{{ $student->first_name }} {{ $student->last_name }}</span></td>
        <td><span class="label">Admission No</span><br><span class="value">{{ $student->admission_number }}</span></td>
        <td><span class="label">Gender</span><br><span class="value">{{ $student->gender }}</span></td>
    </tr>
    <tr>
        <td><span class="label">Class</span><br><span class="value">{{ $class->name }}</span></td>
        <td><span class="label">Session</span><br><span class="value">{{ $session }}</span></td>
        <td><span class="label">Term</span><br><span class="value">{{ $term }}</span></td>
    </tr>
</table>

<!-- ACADEMIC TABLE -->
<div class="section-title">Academic Performance</div>

<table>
    <thead>
        <tr>
            <th>Subject</th>
            <th class="text-center">CA</th>
            <th class="text-center">Exam</th>
            <th class="text-center">Total</th>
            <th class="text-center">Grade</th>
            <th class="text-center">Pos</th>
            <th class="text-right">Avg</th>
        </tr>
    </thead>
    <tbody>
        @forelse($subjects as $subject)
        <tr>
            <td>{{ $subject->subject->name }}</td>
            <td class="text-center">{{ $subject->ca_score }}</td>
            <td class="text-center">{{ $subject->exam_score }}</td>
            <td class="text-center"><strong>{{ $subject->total }}</strong></td>
            <td class="text-center">{{ $subject->grade }}</td>
            <td class="text-center">{{ $subject->subject_position }}</td>
            <td class="text-right">{{ $subject->class_average }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center">No records available</td>
        </tr>
        @endforelse
    </tbody>
</table>

<!-- SUMMARY -->
<div class="section-title">Summary</div>

<table class="summary">
    <tr>
        <td>Total Score</td>
        <td>{{ $result->total_score ?? 0 }}</td>
    </tr>
    <tr>
        <td>Average</td>
        <td>{{ $result->average_score ?? 0 }}</td>
    </tr>
    <tr>
        <td>Position</td>
        <td>{{ $result ? ordinal($result->overall_position) : '-' }}</td>
    </tr>
</table>

<!-- REMARKS -->
<div class="remarks">
    <div class="section-title">Remarks</div>
    <p><strong>Class Teacher:</strong> {{ $teacher_remark }}</p>
    <p><strong>Head Teacher:</strong> {{ $head_remark }}</p>
</div>

<!-- SIGNATURE -->
<table class="signature">
    <tr>
        <td>__________________________<br>Class Teacher</td>
        <td>__________________________<br>Head Teacher</td>
    </tr>
</table>

<!-- FOOTER -->
<div class="footer">
    This report card is computer generated and valid without signature.
</div>

</body>
</html>