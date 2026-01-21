<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CSC Form No. 212 - {{ $pds->full_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 14pt;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 12pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .section-title {
            background-color: #003366;
            color: white;
            padding: 5px;
            font-weight: bold;
            margin-top: 15px;
        }
        .field-label {
            font-weight: bold;
            width: 40%;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PERSONAL DATA SHEET</h1>
        <h2>Civil Service Form No. 212</h2>
        <p><em>Revised 2017</em></p>
    </div>

    <div class="section-title">I. PERSONAL INFORMATION</div>
    <table>
        <tr>
            <td class="field-label">SURNAME</td>
            <td>{{ strtoupper($pds->surname) }}</td>
        </tr>
        <tr>
            <td class="field-label">FIRST NAME</td>
            <td>{{ strtoupper($pds->first_name) }}</td>
        </tr>
        <tr>
            <td class="field-label">MIDDLE NAME</td>
            <td>{{ strtoupper($pds->middle_name ?? 'N/A') }}</td>
        </tr>
        <tr>
            <td class="field-label">NAME EXTENSION (JR., SR)</td>
            <td>{{ strtoupper($pds->name_extension ?? 'N/A') }}</td>
        </tr>
        <tr>
            <td class="field-label">DATE OF BIRTH</td>
            <td>{{ $pds->date_of_birth?->format('m/d/Y') }}</td>
        </tr>
        <tr>
            <td class="field-label">PLACE OF BIRTH</td>
            <td>{{ $pds->place_of_birth }}</td>
        </tr>
        <tr>
            <td class="field-label">SEX</td>
            <td>{{ $pds->sex }}</td>
        </tr>
        <tr>
            <td class="field-label">CIVIL STATUS</td>
            <td>{{ $pds->civil_status }}</td>
        </tr>
        <tr>
            <td class="field-label">HEIGHT (m)</td>
            <td>{{ $pds->height ?: 'N/A' }}</td>
        </tr>
        <tr>
            <td class="field-label">WEIGHT (kg)</td>
            <td>{{ $pds->weight ?: 'N/A' }}</td>
        </tr>
        <tr>
            <td class="field-label">BLOOD TYPE</td>
            <td>{{ $pds->blood_type ?: 'N/A' }}</td>
        </tr>
        <tr>
            <td class="field-label">CITIZENSHIP</td>
            <td>{{ $pds->citizenship }}</td>
        </tr>
    </table>

    <div class="section-title">CONTACT INFORMATION</div>
    <table>
        <tr>
            <td class="field-label">RESIDENTIAL ADDRESS</td>
            <td>{{ $pds->residential_city }}, {{ $pds->residential_province }}</td>
        </tr>
        <tr>
            <td class="field-label">PERMANENT ADDRESS</td>
            <td>{{ $pds->permanent_city }}, {{ $pds->permanent_province }}</td>
        </tr>
        <tr>
            <td class="field-label">TELEPHONE NO.</td>
            <td>{{ $pds->telephone_no ?: 'N/A' }}</td>
        </tr>
        <tr>
            <td class="field-label">MOBILE NO.</td>
            <td>{{ $pds->mobile_no }}</td>
        </tr>
        <tr>
            <td class="field-label">E-MAIL ADDRESS</td>
            <td>{{ $pds->email_address }}</td>
        </tr>
    </table>

    <div class="section-title">GOVERNMENT ISSUED ID</div>
    <table>
        <tr>
            <td class="field-label">GSIS ID NO.</td>
            <td>{{ $pds->gsis_id_no ?: 'N/A' }}</td>
        </tr>
        <tr>
            <td class="field-label">PAG-IBIG ID NO.</td>
            <td>{{ $pds->pagibig_id_no ?: 'N/A' }}</td>
        </tr>
        <tr>
            <td class="field-label">PHILHEALTH NO.</td>
            <td>{{ $pds->philhealth_no ?: 'N/A' }}</td>
        </tr>
        <tr>
            <td class="field-label">SSS NO.</td>
            <td>{{ $pds->sss_no ?: 'N/A' }}</td>
        </tr>
        <tr>
            <td class="field-label">TIN NO.</td>
            <td>{{ $pds->tin_no ?: 'N/A' }}</td>
        </tr>
        <tr>
            <td class="field-label">AGENCY EMPLOYEE NO.</td>
            <td>{{ $pds->agency_employee_no ?: 'N/A' }}</td>
        </tr>
    </table>

    @if($pds->educationalBackgrounds->count() > 0)
    <div class="section-title">II. EDUCATIONAL BACKGROUND</div>
    <table>
        <thead>
            <tr>
                <th>LEVEL</th>
                <th>NAME OF SCHOOL</th>
                <th>BASIC EDUCATION/DEGREE/COURSE</th>
                <th>PERIOD OF ATTENDANCE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pds->educationalBackgrounds as $edu)
            <tr>
                <td>{{ $edu->level }}</td>
                <td>{{ $edu->school_name }}</td>
                <td>{{ $edu->basic_education_degree ?: 'N/A' }}</td>
                <td>{{ $edu->year_from }} - {{ $edu->year_to }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($pds->workExperiences->count() > 0)
    <div class="section-title">III. WORK EXPERIENCE</div>
    <table>
        <thead>
            <tr>
                <th>POSITION TITLE</th>
                <th>COMPANY/OFFICE</th>
                <th>INCLUSIVE DATES</th>
                <th>MONTHLY SALARY</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pds->workExperiences as $work)
            <tr>
                <td>{{ $work->position_title }}</td>
                <td>{{ $work->company_name }}</td>
                <td>{{ $work->date_from?->format('m/Y') }} - {{ $work->date_to ? $work->date_to->format('m/Y') : 'Present' }}</td>
                <td>{{ $work->monthly_salary ? '₱' . number_format($work->monthly_salary, 2) : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div style="margin-top: 40px; text-align: center; font-size: 9pt;">
        <p><em>This is a computer-generated document. Generated on {{ now()->format('F d, Y h:i A') }}</em></p>
        <p><strong>Republic of the Philippines - Civil Service Commission</strong></p>
    </div>
</body>
</html>
