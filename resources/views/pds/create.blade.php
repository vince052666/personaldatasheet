@extends('layouts.app')

@section('title', isset($pd) ? 'Edit PDS' : 'Create New PDS')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">
            {{ isset($pd) ? 'Edit Personal Data Sheet' : 'Create New Personal Data Sheet' }}
        </h1>
        <p class="mt-2 text-sm text-gray-600">Civil Service Form No. 212 (Revised 2017)</p>
    </div>

    <form method="POST" action="{{ isset($pd) ? route('pds.update', $pd) : route('pds.store') }}" class="space-y-6">
        @csrf
        @if(isset($pd))
            @method('PUT')
        @endif

        <!-- Personal Information -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 border-b pb-2">I. PERSONAL INFORMATION</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">SURNAME <span class="text-red-500">*</span></label>
                    <input type="text" name="surname" value="{{ old('surname', $pd->surname ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('surname') border-red-500 @enderror">
                    @error('surname')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">FIRST NAME <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name', $pd->first_name ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('first_name') border-red-500 @enderror">
                    @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">MIDDLE NAME</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name', $pd->middle_name ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">NAME EXTENSION (e.g. Jr., Sr.)</label>
                    <input type="text" name="name_extension" value="{{ old('name_extension', $pd->name_extension ?? '') }}" placeholder="Jr., Sr., III" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">DATE OF BIRTH <span class="text-red-500">*</span></label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', isset($pd) ? $pd->date_of_birth?->format('Y-m-d') : '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('date_of_birth') border-red-500 @enderror">
                    @error('date_of_birth')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">PLACE OF BIRTH <span class="text-red-500">*</span></label>
                    <input type="text" name="place_of_birth" value="{{ old('place_of_birth', $pd->place_of_birth ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('place_of_birth') border-red-500 @enderror">
                    @error('place_of_birth')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">SEX <span class="text-red-500">*</span></label>
                    <select name="sex" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('sex') border-red-500 @enderror">
                        <option value="">Select</option>
                        <option value="Male" {{ old('sex', $pd->sex ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('sex', $pd->sex ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                    @error('sex')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">CIVIL STATUS <span class="text-red-500">*</span></label>
                    <select name="civil_status" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('civil_status') border-red-500 @enderror">
                        <option value="">Select</option>
                        <option value="Single" {{ old('civil_status', $pd->civil_status ?? '') == 'Single' ? 'selected' : '' }}>Single</option>
                        <option value="Married" {{ old('civil_status', $pd->civil_status ?? '') == 'Married' ? 'selected' : '' }}>Married</option>
                        <option value="Widowed" {{ old('civil_status', $pd->civil_status ?? '') == 'Widowed' ? 'selected' : '' }}>Widowed</option>
                        <option value="Separated" {{ old('civil_status', $pd->civil_status ?? '') == 'Separated' ? 'selected' : '' }}>Separated</option>
                        <option value="Divorced" {{ old('civil_status', $pd->civil_status ?? '') == 'Divorced' ? 'selected' : '' }}>Divorced</option>
                    </select>
                    @error('civil_status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">HEIGHT (m)</label>
                    <input type="number" step="0.01" name="height" value="{{ old('height', $pd->height ?? '') }}" placeholder="1.65" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">WEIGHT (kg)</label>
                    <input type="number" step="0.01" name="weight" value="{{ old('weight', $pd->weight ?? '') }}" placeholder="65" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">BLOOD TYPE</label>
                    <select name="blood_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Select</option>
                        @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $type)
                            <option value="{{ $type }}" {{ old('blood_type', $pd->blood_type ?? '') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">CITIZENSHIP <span class="text-red-500">*</span></label>
                    <input type="text" name="citizenship" value="{{ old('citizenship', $pd->citizenship ?? 'Filipino') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('citizenship') border-red-500 @enderror">
                    @error('citizenship')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 border-b pb-2">CONTACT INFORMATION</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">MOBILE NO. <span class="text-red-500">*</span></label>
                    <input type="text" name="mobile_no" value="{{ old('mobile_no', $pd->mobile_no ?? '') }}" required placeholder="09XX-XXX-XXXX" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('mobile_no') border-red-500 @enderror">
                    @error('mobile_no')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">EMAIL ADDRESS <span class="text-red-500">*</span></label>
                    <input type="email" name="email_address" value="{{ old('email_address', $pd->email_address ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('email_address') border-red-500 @enderror">
                    @error('email_address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">TELEPHONE NO.</label>
                    <input type="text" name="telephone_no" value="{{ old('telephone_no', $pd->telephone_no ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">DEPARTMENT</label>
                    <input type="text" name="department" value="{{ old('department', $pd->department ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">POSITION</label>
                    <input type="text" name="position" value="{{ old('position', $pd->position ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
            </div>
        </div>

        <!-- Government IDs -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 border-b pb-2">GOVERNMENT ISSUED ID</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">GSIS ID NO.</label>
                    <input type="text" name="gsis_id_no" value="{{ old('gsis_id_no', $pd->gsis_id_no ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">PAG-IBIG ID NO.</label>
                    <input type="text" name="pagibig_id_no" value="{{ old('pagibig_id_no', $pd->pagibig_id_no ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">PHILHEALTH NO.</label>
                    <input type="text" name="philhealth_no" value="{{ old('philhealth_no', $pd->philhealth_no ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">SSS NO.</label>
                    <input type="text" name="sss_no" value="{{ old('sss_no', $pd->sss_no ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">TIN NO.</label>
                    <input type="text" name="tin_no" value="{{ old('tin_no', $pd->tin_no ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">AGENCY EMPLOYEE NO.</label>
                    <input type="text" name="agency_employee_no" value="{{ old('agency_employee_no', $pd->agency_employee_no ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('pds.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                {{ isset($pd) ? 'Update PDS' : 'Create PDS' }}
            </button>
        </div>
    </form>
</div>
@endsection
