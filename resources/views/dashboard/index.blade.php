@extends('layouts.app')

@section('title', 'Dashboard - PDS Management System')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">HR Dashboard</h1>
        <p class="mt-2 text-sm text-gray-600">Overview of Personal Data Sheet records and quality metrics</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total PDS</dt>
                            <dd class="text-3xl font-semibold text-gray-900">{{ number_format($stats['total_pds']) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Pending Review</dt>
                            <dd class="text-3xl font-semibold text-gray-900">{{ number_format($stats['pending_review']) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Approved</dt>
                            <dd class="text-3xl font-semibold text-gray-900">{{ number_format($stats['approved']) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Draft</dt>
                            <dd class="text-3xl font-semibold text-gray-900">{{ number_format($stats['draft']) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quality Score Overview -->
    <div class="bg-white shadow rounded-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Data Quality Overview</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ number_format($qualityStats['average_overall'], 1) }}%</div>
                <div class="text-sm text-gray-600">Average Quality Score</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ number_format($qualityStats['average_completeness'], 1) }}%</div>
                <div class="text-sm text-gray-600">Completeness</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">{{ number_format($qualityStats['average_accuracy'], 1) }}%</div>
                <div class="text-sm text-gray-600">Accuracy</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-indigo-600">{{ number_format($qualityStats['average_consistency'], 1) }}%</div>
                <div class="text-sm text-gray-600">Consistency</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent PDS -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Recent PDS Submissions</h2>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($recentPDS as $pds)
                    <div class="px-6 py-4 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-900">{{ $pds->full_name }}</h3>
                                <p class="text-sm text-gray-500">{{ $pds->department ?? 'N/A' }}</p>
                            </div>
                            <div class="ml-4 flex-shrink-0">
                                @if($pds->dataQualityScore)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $pds->dataQualityScore->overall_score >= 75 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ number_format($pds->dataQualityScore->overall_score, 0) }}%
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="mt-2 text-sm text-gray-500">
                            {{ $pds->created_at->diffForHumans() }}
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-4 text-center text-gray-500">No recent PDS submissions</div>
                @endforelse
            </div>
        </div>

        <!-- Low Quality Alerts -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Quality Alerts</h2>
                <p class="text-sm text-gray-600">PDS records requiring attention (< 60% quality score)</p>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($lowQualityPDS->take(10) as $pds)
                    <div class="px-6 py-4 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-900">{{ $pds->full_name }}</h3>
                                <p class="text-sm text-gray-500">{{ $pds->department ?? 'N/A' }}</p>
                            </div>
                            <div class="ml-4 flex-shrink-0">
                                @if($pds->dataQualityScore)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        {{ number_format($pds->dataQualityScore->overall_score, 0) }}%
                                    </span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('pds.edit', $pds) }}" class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                            Review & Fix →
                        </a>
                    </div>
                @empty
                    <div class="px-6 py-4 text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-2">All PDS records meet quality standards</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="bg-white shadow rounded-lg mt-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Recent Activities</h2>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($recentActivities as $activity)
                <div class="px-6 py-3 text-sm">
                    <span class="font-medium">{{ $activity->user->name ?? 'System' }}</span>
                    <span class="text-gray-600">{{ $activity->action }}</span>
                    <span class="text-gray-500">{{ $activity->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <div class="px-6 py-4 text-center text-gray-500">No recent activities</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
