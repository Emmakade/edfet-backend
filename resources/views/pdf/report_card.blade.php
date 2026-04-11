@php
if (! function_exists('ordinal')) {
    function ordinal($number) {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        if (($number % 100) >= 11 && ($number % 100) <= 13) {
            return $number . 'th';
        }

        return $number . $ends[$number % 10];
    }
}

$studentName = trim($student->full_name ?? (($student->surname ?? '') . ' ' . ($student->first_name ?? '')));
$termName = $term->name ?? ('Term ' . ($result->term_id ?? ''));
$averageScore = (float) ($result->average_score ?? 0);
$overallGrade = $result->grade ?? null;
$overallPosition = $result && isset($result->overall_position) ? ordinal((int) $result->overall_position) : '-';
$school = $school ?? $enrollment->schoolClass->school ?? \App\Models\School::first();
$schoolExtra = is_array($school?->extra ?? null) ? $school->extra : [];
$schoolLogo = $schoolLogo ?? $schoolExtra['school_logo'] ?? $schoolExtra['logo'] ?? null;
$schoolEmail = $schoolEmail ?? $schoolExtra['school_email'] ?? $schoolExtra['email'] ?? null;
$schoolName = $school->name ?? config('app.name') ?? 'School';
$schoolAddress = $school->address ?? null;
$schoolMailbox = $school->mailbox ?? null;
$schoolPhone = $school->phone ?? null;
$schoolMotto = $school->motto ?? null;
$attendanceOpened = $attendance->times_school_opened ?? 0;
$attendancePresent = $attendance->times_present ?? 0;
$nextTermBegins = $school?->next_term_begins ? $school->next_term_begins->format('M j, Y') : 'N/A';
$hmsign = $hmsign ?? $schoolExtra['hm_sign'] ?? $schoolExtra['sign'] ?? null;

$performancePalette = static function ($score) {
    $score = (float) $score;

    if ($score < 40) {
        return ['bg' => '#fde8e8', 'text' => '#b42318', 'label' => 'Needs Improvement'];
    }

    if ($score < 60) {
        return ['bg' => '#fff4e5', 'text' => '#b54708', 'label' => 'Fair'];
    }

    if ($score < 75) {
        return ['bg' => '#ecfdf3', 'text' => '#027a48', 'label' => 'Good'];
    }

    return ['bg' => '#dcfae6', 'text' => '#05603a', 'label' => 'Excellence'];
};

$overallPalette = $performancePalette($averageScore);
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; margin:0; padding:0; }
        body {
            margin: 0;
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            background: #ffffff;
        }
        .page {
            padding: 24px 16px 18px;
        }
        .report-shell {
            border: 1px solid #d0d5dd;
            border-radius: 18px;
            overflow: hidden;
        }
        .hero {
            padding: 18px 22px 16px;
            background: #0ea5e9;
            color: #ffffff;
        }
        .school-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.22);
        }
        .school-logo-cell {
            width: 86px;
            vertical-align: top;
        }
        .school-logo-wrap {
            width: 68px;
            height: 68px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            vertical-align: middle;
            overflow: hidden;
        }
        .school-logo {
            width: 62px;
            height: 62px;
            object-fit: contain;
            margin-top: 3px;
        }

        .school-sign {
            height: 30px
        }
        .school-logo-fallback {
            display: inline-block;
            margin-top: 22px;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 0.08em;
        }
        .school-meta-cell {
            vertical-align: top;
        }
        .school-name {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .school-line {
            margin-top: 3px;
            font-size: 10px;
            opacity: 0.96;
        }
        .school-motto {
            margin-top: 5px;
            font-size: 10px;
            font-style: italic;
            opacity: 0.95;
        }
        .hero-table,
        .info-grid,
        .summary-grid,
        .marks-table {
            width: 100%;
            border-collapse: collapse;
        }
        .hero-title {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .hero-subtitle {
            margin-top: 4px;
            font-size: 10px;
            opacity: 0.92;
        }
        .hero-badge {
            text-align: right;
        }
        .hero-badge .term-pill {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.28);
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .content {
            padding: 18px;
            background: #f8fafc;
        }
        .section-title {
            margin: 0 0 10px;
            color: #0369a1; /* cool cyan */
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .info-card,
        .summary-card,
        .remark-card {
            width: 100%;
            border: 1px solid #e0f2fe;
            border-radius: 14px;
            background: #ffffff;
        }
        .info-card {
            padding: 12px 14px;
        }
        .info-grid td {
            width: 33.33%;
            padding: 0 8px 10px 0;
            vertical-align: top;
        }
        .info-label {
            display: block;
            margin-bottom: 2px;
            color: #667085;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .info-value {
            color: #0f172a;
        }
        .spacer {
            height: 14px;
        }
        .marks-wrap {
            border: 1px solid #e0f2fe;
            border-radius: 14px;
            overflow: hidden;
            background: #ffffff;
        }
        .marks-table th {
            padding: 10px 8px;
            background: #0ea5e9;
            color: #ffffff;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .marks-table th:last-child {
            border-right: none;
        }
        .marks-table td {
            padding: 9px 8px;
            border-top: 1px solid #f2e8e8;
            color: #344054;
        }
        .marks-table tbody tr:nth-child(even) {
            background: #f0f9ff;
        }
        .subject-name {
            font-weight: bold;
            color: #101828;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .score-chip,
        .grade-chip,
        .status-chip {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: bold;
        }
        .summary-grid td {
            width: 25%;
            padding: 12px 10px;
            border-right: 1px solid #f0e0e0;
            vertical-align: top;
        }
        .summary-grid td:last-child {
            border-right: none;
        }
        .summary-label {
            display: block;
            margin-bottom: 6px;
            color: #667085;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .summary-value {
            color: #0284c7;
            font-size: 16px;
            font-weight: bold;
        }
        .summary-note {
            margin-top: 4px;
            color: #475467;
            font-size: 9px;
        }
        .remark-card {
            padding: 14px;
        }
        .remark-block {
            border-left: 4px solid #0ea5e9;
            padding: 10px 12px;
            border-radius: 10px;
            background: #f0f9ff;
        }
        .remark-block:last-child {
            margin-bottom: 0;
        }
        .remark-label {
            display: block;
            margin-bottom: 4px;
            color: #667085;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .remark-value {
            color: #101828;
            font-size: 11px;
        }
        .footer-note {
            margin-top: 12px;
            color: #667085;
            font-size: 9px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="report-shell">
        <div class="hero">
            <table class="school-header">
                <tr>
                    <td class="school-logo-cell">
                        <div class="school-logo-wrap">
                            @if($schoolLogo)
                                <img src="{{ $schoolLogo }}" alt="School Logo" class="school-logo">
                            @else
                                <span class="school-logo-fallback">{{ strtoupper(substr($schoolName ?? 'S', 0, 1)) }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="school-meta-cell">
                        <div class="school-name">GOMAL BAPTIST NURSERY AND PRIMARY SCHOOL</div>
                        @if($schoolAddress)
                            <div class="school-line">Old Osogbo road, Ogbomoso</div>
                        @endif
                        <div class="school-line">
                                P.O. Box: 1981
                            @if($schoolMailbox && ($schoolPhone || $schoolEmail))
                                &nbsp; | &nbsp;
                            @endif
                            @if($schoolPhone)
                                Phone: {{ $schoolPhone }}
                            @endif
                            @if($schoolPhone && $schoolEmail)
                                &nbsp; | &nbsp;
                            @endif
                            @if($schoolEmail)
                                Email: {{ $schoolEmail }}
                            @endif
                        </div>
                        
                            <div class="school-motto">Arise and Shine</div>
                        
                    </td>
                </tr>
            </table>
            <table class="hero-table">
                <tr>
                    <td>
                        <div class="hero-title">Student Report Card</div>
                        <div class="hero-subtitle">
                            Academic performance summary for {{ $studentName ?: 'Student' }}
                        </div>
                    </td>
                    <td class="hero-badge">
                        <span class="term-pill">{{ $termName }}</span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="content">
            <p class="section-title">Student Information</p>
            <div class="info-card">
                <table class="info-grid">
                    <tr>
                        <td>
                            <span class="info-label">Student Name</span>
                            <span class="info-value">{{ $studentName ?: 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="info-label">Admission Number</span>
                            <span class="info-value">{{ $student->admission_number ?: 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="info-label">Gender</span>
                            <span class="info-value">{{ ucfirst($student->gender ?: 'N/A') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="info-label">Class</span>
                            <span class="info-value">{{ $enrollment->schoolClass->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="info-label">Session</span>
                            <span class="info-value">{{ $enrollment->session->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="info-label">Overall Status</span>
                            <span class="status-chip" style="background: {{ $overallPalette['bg'] }}; color: {{ $overallPalette['text'] }};">
                                {{ $overallPalette['label'] }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="info-label">Days School Opened</span>
                            <span class="info-value">{{ $attendanceOpened }}</span>
                        </td>
                        <td>
                            <span class="info-label">Days Present</span>
                            <span class="info-value">{{ $attendancePresent }}</span>
                        </td>
                        <td>
                            <span class="info-label">Next Term Begins</span>
                            <span class="info-value">{{ $nextTermBegins }}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="spacer"></div>

            <p class="section-title">Academic Performance</p>
            <div class="marks-wrap">
                <table class="marks-table">
                    <thead>
                        <tr>
                            <th style="width: 24%;">Subject</th>
                            @if(isset($subjects[0]))
                                @foreach($subjects[0]->assessments as $assessment)
                                    <th class="text-center">{{ $assessment->name }}({{$assessment->max_score}})</th>
                                @endforeach
                            @endif
                            <th class="text-center">Total</th>
                            <th class="text-center">Grade</th>
                            <th class="text-center">Position</th>
                            <th class="text-center">Average</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subjects as $subject)
                            @php
                                $subjectPalette = $performancePalette($subject->total ?? 0);
                            @endphp
                            <tr>
                                <td class="subject-name">{{ $subject->subject->name }}</td>

                                @foreach($subject->assessments as $assessment)
                                    @php
                                        $assessmentPalette = $performancePalette($assessment->score ?? 0);
                                    @endphp
                                    <td class="text-center">
                                        <span class="score-chip" style="background: {{ $assessmentPalette['bg'] }}; color: {{ $assessmentPalette['text'] }};">
                                            {{ $assessment->score ?? 0 }}
                                        </span>
                                    </td>
                                @endforeach

                                <td class="text-center">
                                    <span class="score-chip" style="background: {{ $subjectPalette['bg'] }}; color: {{ $subjectPalette['text'] }};">
                                        {{ $subject->total ?? 0 }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="grade-chip" style="background: {{ $subjectPalette['bg'] }}; color: {{ $subjectPalette['text'] }};">
                                        {{ $subject->grade ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $subject->subject_position ? ordinal((int) $subject->subject_position) : '-' }}</td>
                                <td class="text-center">{{ number_format((float) ($subject->class_average ?? 0), 1) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="spacer"></div>

            <p class="section-title">Performance Summary</p>
            <div class="summary-card">
                <table class="summary-grid">
                    <tr>
                        <td>
                            <span class="summary-label">Total Score</span>
                            <span class="summary-value">{{ number_format((float) ($result->total_score ?? 0), 0) }}</span>
                            <div class="summary-note">Combined score across all subjects</div>
                        </td>
                        <td>
                            <span class="summary-label">Average</span>
                            <span class="summary-value">{{ number_format($averageScore, 1) }}</span>
                            <div class="summary-note">Overall class performance average</div>
                        </td>
                        <td>
                            <span class="summary-label">Position</span>
                            <span class="summary-value">{{ $overallPosition }}</span>
                            <div class="summary-note">Student rank in class</div>
                        </td>
                        <td>
                            <span class="summary-label">Grade / Status</span>
                            <span class="grade-chip" style="background: {{ $overallPalette['bg'] }}; color: {{ $overallPalette['text'] }}; font-size: 10px; padding: 6px 10px;">
                                {{ $overallGrade ?: $overallPalette['label'] }}
                            </span>
                            <div class="summary-note">Performance band for this term</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="spacer"></div>

            <p class="section-title">Remarks</p>
            <div class="remark-card">
                <div class="remark-block">
                    <span class="remark-label">Class Teacher's Remark</span>
                    <div class="remark-value">{{ $remark->class_teacher_remark ?: 'No teacher remark available.' }}</div>
                </div>
                <div class="remark-block">
                    <span class="remark-label">Head Teacher's Remark</span> 
                    <span> {{$hmsign}}</span> <img src="{{ $hmsign }}" class="school-sign">
                    <div class="remark-value">{{ $remark->head_teacher_remark ?: 'No head teacher remark available.' }}</div>
                </div>
            </div>

            <div class="footer-note" style="margin-top: 8px; font-size: 8px; color: #6b7280;">
                <strong>Key to Rating:</strong> (70-100: A, Excellent) | (60-69: B, Very Good) | (50-59: C, Good) | (45-49: D, Fair) | (40-44: E, Pass) | (0-39: F, Fail)
            </div>
            <div class="footer-note">
                This report card was generated electronically and is valid without manual alteration.
            </div>

        </div>
    </div>
</div>
</body>
</html>



