@extends('layouts.app')

@section('title', 'View PDS - ' . $pd->full_name)

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $pd->full_name }}</h1>
            <p class="mt-2 text-sm text-gray-600">Personal Data Sheet - CSC Form No. 212</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('pds.edit', $pd) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Edit
            </a>
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                Print
            </button>
        </div>
    </div>

    <!-- Status and Quality Score -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Status</h3>
                @php
                    $statusColors = [
                        'draft' => 'bg-gray-100 text-gray-800',
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'approved' => 'bg-green-100 text-green-800',
                    ];
                @endphp
                <span class="mt-1 inline-flex px-3 py-1 text-sm font-semibold rounded-full {{ $statusColors[$pd->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst($pd->status) }}
                </span>
            </div>

            @if($pd->dataQualityScore)
            <div>
                <h3 class="text-sm font-medium text-gray-500">Data Quality Score</h3>
                <div class="mt-1 flex items-center">
                    <span class="text-2xl font-bold text-gray-900">{{ number_format($pd->dataQualityScore->overall_score, 0) }}%</span>
                    <div class="ml-3 flex-1">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $pd->dataQualityScore->overall_score }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500">Quality Breakdown</h3>
                <div class="mt-1 text-sm">
                    <div>Completeness: <span class="font-semibold">{{ number_format($pd->dataQualityScore->completeness_score, 0) }}%</span></div>
                    <div>Accuracy: <span class="font-semibold">{{ number_format($pd->dataQualityScore->accuracy_score, 0) }}%</span></div>
                    <div>Consistency: <span class="font-semibold">{{ number_format($pd->dataQualityScore->consistency_score, 0) }}%</span></div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Personal Information -->
    <div class="bg-white shadow rounded-lg p-6 mb-6 print:shadow-none">
        <h2 class="text-xl font-bold text-gray-900 mb-4 border-b pb-2">I. PERSONAL INFORMATION</h2>
        
        <div class="grid grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">Surname</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->surname }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">First Name</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->first_name }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Middle Name</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->middle_name ?: 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Name Extension</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->name_extension ?: 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Date of Birth</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->date_of_birth?->format('F d, Y') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Place of Birth</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->place_of_birth }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Sex</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->sex }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Civil Status</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->civil_status }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Height</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->height ? $pd->height . ' m' : 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Weight</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->weight ? $pd->weight . ' kg' : 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Blood Type</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->blood_type ?: 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Citizenship</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->citizenship }}</dd>
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="bg-white shadow rounded-lg p-6 mb-6 print:shadow-none">
        <h2 class="text-xl font-bold text-gray-900 mb-4 border-b pb-2">CONTACT INFORMATION</h2>
        
        <div class="grid grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">Mobile No.</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->mobile_no }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->email_address }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Telephone No.</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->telephone_no ?: 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Department</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->department ?: 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Position</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->position ?: 'N/A' }}</dd>
            </div>
        </div>
    </div>

    <!-- Educational Background -->
    @if($pd->educationalBackgrounds->count() > 0)
    <div class="bg-white shadow rounded-lg p-6 mb-6 print:shadow-none">
        <h2 class="text-xl font-bold text-gray-900 mb-4 border-b pb-2">II. EDUCATIONAL BACKGROUND</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">School Name</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Degree/Course</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($pd->educationalBackgrounds as $edu)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $edu->level }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $edu->school_name }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $edu->basic_education_degree ?: 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $edu->year_from }} - {{ $edu->year_to }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Work Experience -->
    @if($pd->workExperiences->count() > 0)
    <div class="bg-white shadow rounded-lg p-6 mb-6 print:shadow-none">
        <h2 class="text-xl font-bold text-gray-900 mb-4 border-b pb-2">III. WORK EXPERIENCE</h2>
        
        <div class="space-y-4">
            @foreach($pd->workExperiences as $work)
            <div class="border-l-4 border-blue-500 pl-4">
                <h3 class="font-semibold text-gray-900">{{ $work->position_title }}</h3>
                <p class="text-sm text-gray-600">{{ $work->company_name }}</p>
                <p class="text-sm text-gray-500">
                    {{ $work->date_from?->format('M Y') }} - {{ $work->date_to ? $work->date_to->format('M Y') : 'Present' }}
                </p>
                @if($work->monthly_salary)
                    <p class="text-sm text-gray-600">Monthly Salary: ₱{{ number_format($work->monthly_salary, 2) }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Government IDs -->
    <div class="bg-white shadow rounded-lg p-6 mb-6 print:shadow-none">
        <h2 class="text-xl font-bold text-gray-900 mb-4 border-b pb-2">GOVERNMENT ISSUED IDs</h2>
        
        <div class="grid grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">GSIS ID No.</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->gsis_id_no ?: 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Pag-IBIG ID No.</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->pagibig_id_no ?: 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">PhilHealth No.</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->philhealth_no ?: 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">SSS No.</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->sss_no ?: 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">TIN No.</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->tin_no ?: 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Agency Employee No.</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $pd->agency_employee_no ?: 'N/A' }}</dd>
            </div>
        </div>
    </div>

    <!-- Quality Suggestions -->
    @if($pd->dataQualityScore && count($pd->dataQualityScore->suggestions) > 0)
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6 print:hidden">
        <h2 class="text-lg font-bold text-yellow-900 mb-3">💡 Suggestions for Improvement</h2>
        <ul class="list-disc list-inside space-y-1">
            @foreach($pd->dataQualityScore->suggestions as $suggestion)
                <li class="text-sm text-yellow-800">{{ $suggestion }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="flex justify-between print:hidden">
        <a href="{{ route('pds.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            ← Back to List
        </a>
    </div>
</div>

<style>
    @media print {
        body { background: white; }
        .print\:shadow-none { box-shadow: none !important; }
        .print\:hidden { display: none !important; }
    }
</style>
@endsection
