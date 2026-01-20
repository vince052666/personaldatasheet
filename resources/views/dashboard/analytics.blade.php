@extends('layouts.app')

@section('title', 'Analytics Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Analytics Dashboard</h1>
        <p class="mt-2 text-sm text-gray-600">Detailed insights and data quality analytics</p>
    </div>

    <!-- Quality Distribution -->
    <div class="bg-white shadow rounded-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Data Quality Distribution</h2>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-3xl font-bold text-green-600">{{ $qualityStats['excellent'] }}</div>
                <div class="text-sm text-gray-600">Excellent (≥90%)</div>
            </div>
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <div class="text-3xl font-bold text-blue-600">{{ $qualityStats['good'] }}</div>
                <div class="text-sm text-gray-600">Good (75-89%)</div>
            </div>
            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                <div class="text-3xl font-bold text-yellow-600">{{ $qualityStats['fair'] }}</div>
                <div class="text-sm text-gray-600">Fair (60-74%)</div>
            </div>
            <div class="text-center p-4 bg-orange-50 rounded-lg">
                <div class="text-3xl font-bold text-orange-600">{{ $qualityStats['poor'] }}</div>
                <div class="text-sm text-gray-600">Poor (40-59%)</div>
            </div>
            <div class="text-center p-4 bg-red-50 rounded-lg">
                <div class="text-3xl font-bold text-red-600">{{ $qualityStats['critical'] }}</div>
                <div class="text-sm text-gray-600">Critical (<40%)</div>
            </div>
        </div>
    </div>

    <!-- Average Scores -->
    <div class="bg-white shadow rounded-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Average Quality Scores</h2>
        <div class="space-y-4">
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-sm font-medium text-gray-700">Overall Quality</span>
                    <span class="text-sm font-medium text-gray-700">{{ number_format($qualityStats['average_overall'], 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-blue-600 h-3 rounded-full" style="width: {{ $qualityStats['average_overall'] }}%"></div>
                </div>
            </div>

            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-sm font-medium text-gray-700">Completeness</span>
                    <span class="text-sm font-medium text-gray-700">{{ number_format($qualityStats['average_completeness'], 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-green-600 h-3 rounded-full" style="width: {{ $qualityStats['average_completeness'] }}%"></div>
                </div>
            </div>

            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-sm font-medium text-gray-700">Accuracy</span>
                    <span class="text-sm font-medium text-gray-700">{{ number_format($qualityStats['average_accuracy'], 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-purple-600 h-3 rounded-full" style="width: {{ $qualityStats['average_accuracy'] }}%"></div>
                </div>
            </div>

            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-sm font-medium text-gray-700">Consistency</span>
                    <span class="text-sm font-medium text-gray-700">{{ number_format($qualityStats['average_consistency'], 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-indigo-600 h-3 rounded-full" style="width: {{ $qualityStats['average_consistency'] }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- PDS per Department -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">PDS by Department</h2>
            <div class="space-y-3">
                @foreach($pdsPerDepartment as $dept)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">{{ $dept->department ?: 'Unassigned' }}</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $dept->total }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- PDS per Month -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Monthly Submissions (Last 12 Months)</h2>
            <div class="space-y-3">
                @foreach($pdsPerMonth as $month)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">{{ \Carbon\Carbon::parse($month->month . '-01')->format('F Y') }}</span>
                        <div class="flex items-center">
                            <div class="w-32 bg-gray-200 rounded-full h-2 mr-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(100, ($month->total / max($pdsPerMonth->max('total'), 1)) * 100) }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ $month->total }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
