@extends('admin.layouts.app')

@section('content')
@php
$user = Auth::user();
@endphp

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-2 sm:p-4">
    <div class="max-w-7xl mx-auto">
        {{-- Enhanced Header --}}
        <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl overflow-hidden border border-white/20 mb-4">
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-center sm:text-left">
                        <h1 class="text-2xl sm:text-3xl font-bold text-white mb-1">
                            <i class="fas fa-users mr-2"></i>Salary Heads Management
                        </h1>
                        <p class="text-blue-100 text-sm">Manage staff salary heads and approvals</p>
                    </div>

                    <div class="flex flex-col sm:flex-row w-full sm:w-auto space-y-3 sm:space-y-0 sm:space-x-3">
                        <a href="{{ route('admin.salary-heads.create') }}" id="createSalaryHeadBtn"
                            class="group relative overflow-hidden bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                            <span class="relative flex items-center justify-center">
                                <svg id="defaultPlusIcon" class="w-5 h-5 mr-2 transition-transform group-hover:rotate-90" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                                </svg>
                                <i id="spinnerIcon" class="hidden fas fa-spinner fa-spin mr-2"></i>
                                <span id="buttonText">New Salary Head</span>
                            </span>
                        </a>

                        <button onclick="bulkApprove()" id="bulkApproveBtn"
                            class="group relative overflow-hidden bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                            <span class="relative flex items-center justify-center">
                                <svg id="bulkApproveIcon" class="w-5 h-5 mr-2 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <i id="bulkApproveSpinnerIcon" class="hidden fas fa-spinner fa-spin mr-2"></i>
                                <span id="bulkApproveButtonText" class="hidden sm:inline">Bulk Approve</span>
                                <span class="sm:hidden">Approve</span>
                            </span>
                        </button>

                        <a href="{{ route('admin.salary-heads.index') }}" id="refreshBtn"
                            class="group relative overflow-hidden bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                            <span class="relative flex items-center justify-center">
                                <svg id="refreshIcon" class="w-5 h-5 mr-2 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <i id="refreshSpinnerIcon" class="hidden fas fa-spinner fa-spin mr-2"></i>
                                <span id="refreshButtonText" class="hidden sm:inline">Refresh</span>
                                <span class="sm:hidden">Refresh</span>
                            </span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Enhanced Filter Section -->
            <div class="p-4 bg-gradient-to-r from-gray-50 to-blue-50 border-t border-gray-200">
                <form action="{{ route('admin.salary-heads.index') }}" method="GET"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

                    <!-- Search Input -->
                    <div class="relative">
                        <input type="text" id="search" name="search" value="{{ request('search') }}"
                            placeholder="Search by Staff ID or Name..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <!-- Role Filter -->
                    <div class="relative">
                        <select name="role_name" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <option value="">All Roles</option>
                            @foreach($availableRoles as $role)
                            <option value="{{ $role->name }}" {{ request('role_name') == $role->name ? 'selected' : '' }}>
                                {{ ucfirst($role->name) }}
                            </option>
                            @endforeach
                        </select>
                        <i class="fas fa-user-tag absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <!-- Status Filter -->
                    <div class="relative">
                        <select name="status" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                        <i class="fas fa-toggle-on absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2">
                        <button type="submit" id="searchBtn"
                            class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-md text-sm">
                            <i class="fas fa-search mr-1"></i>
                            <span class="hidden sm:inline">Search</span>
                        </button>

                        <a href="{{ route('admin.salary-heads.index') }}"
                            class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-medium py-2.5 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-md text-sm text-center flex items-center justify-center">
                            <i class="fas fa-undo mr-1"></i>
                            <span class="hidden sm:inline">Reset</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Success Message --}}
        @if(session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-r-xl shadow-lg" role="alert">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        </div>
        @endif

        {{-- Error Message --}}
        @if(session('error'))
        <div class="bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-r-xl shadow-lg" role="alert">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3 text-lg"></i>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        </div>
        @endif

        {{-- Mobile Cards - Only show on small screens --}}
        <div class="lg:hidden space-y-3">
            @forelse($salaryHeads as $salaryHead)
            <div class="bg-white/90 backdrop-blur-sm rounded-lg shadow-md border border-white/20 overflow-hidden hover:shadow-lg transition-all duration-300">
                <!-- Mobile Card Header -->
                <div class="bg-gradient-to-r from-slate-50 to-blue-50 px-4 py-3 border-b border-gray-100">
                    <div class="flex justify-between items-start">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 text-sm">{{ Str::limit($salaryHead->user->name, 20) }}</h3>
                                <p class="text-xs text-gray-500">Staff ID: {{ $salaryHead->staff_id }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-green-600">
                                {{ $salaryHead->formatted_salary_amount }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Card Body -->
                <div class="p-4">
                    <div class="flex justify-between items-center mb-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $salaryHead->role_name }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $salaryHead->status_badge_class }}">
                            {{ $salaryHead->status_display }}
                        </span>
                    </div>

                    <!-- Mobile Action Buttons -->
                    <div class="grid grid-cols-3 gap-2">
                        <!-- View Button -->
                        <a href="{{ route('admin.salary-heads.show', $salaryHead->id) }}"
                            class="flex flex-col items-center justify-center p-3 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-all duration-300">
                            <i class="fas fa-eye text-sm mb-1"></i>
                            <span class="text-xs font-medium">View</span>
                        </a>

                        @if($salaryHead->canBeApproved())
                        <!-- Approve Button -->
                        <button onclick="approveSalaryHead({{ $salaryHead->id }})"
                            class="flex flex-col items-center justify-center p-3 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition-all duration-300">
                            <i class="fas fa-check text-sm mb-1"></i>
                            <span class="text-xs font-medium">Approve</span>
                        </button>
                        @elseif($salaryHead->canBeEdited())
                        <!-- Edit Button -->
                        <a href="{{ route('admin.salary-heads.edit', $salaryHead->id) }}"
                            class="flex flex-col items-center justify-center p-3 rounded-lg bg-yellow-50 text-yellow-600 hover:bg-yellow-100 transition-all duration-300">
                            <i class="fas fa-edit text-sm mb-1"></i>
                            <span class="text-xs font-medium">Edit</span>
                        </a>
                        @else
                        <!-- Status Display -->
                        <div class="flex flex-col items-center justify-center p-3 rounded-lg bg-gray-50 text-gray-600">
                            <i class="fas fa-info-circle text-sm mb-1"></i>
                            <span class="text-xs font-medium">{{ $salaryHead->status_display }}</span>
                        </div>
                        @endif

                        @if($salaryHead->canBeDeleted())
                        <!-- Delete Button -->
                        <form action="{{ route('admin.salary-heads.destroy', $salaryHead->id) }}"
                            method="POST"
                            onsubmit="return confirm('Are you sure you want to delete this salary head?');"
                            class="w-full">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="w-full flex flex-col items-center justify-center p-3 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-all duration-300">
                                <i class="fas fa-trash-alt text-sm mb-1"></i>
                                <span class="text-xs font-medium">Delete</span>
                            </button>
                        </form>
                        @else
                        <!-- Reject Button -->
                        <button onclick="rejectSalaryHead({{ $salaryHead->id }})"
                            class="flex flex-col items-center justify-center p-3 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-all duration-300">
                            <i class="fas fa-times text-sm mb-1"></i>
                            <span class="text-xs font-medium">Reject</span>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow-lg p-8 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-users text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No salary heads found</h3>
                <p class="text-gray-500 text-sm">Try adjusting your search filters or create a new salary head.</p>
            </div>
            @endforelse
        </div>

        {{-- Desktop Table - Only show on large screens --}}
        <div class="hidden lg:block bg-white/90 backdrop-blur-sm rounded-xl shadow-lg border border-white/20 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-gray-50 to-blue-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-id-badge mr-2"></i>Staff ID
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-user mr-2"></i>Name
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-user-tag mr-2"></i>Role
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-money-bill mr-2"></i>Salary
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-toggle-on mr-2"></i>Status
                            </th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <i class="fas fa-cogs mr-2"></i>Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($salaryHeads as $salaryHead)
                        <tr class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-300">
                            <!-- Checkbox Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($salaryHead->canBeApproved())
                                <input type="checkbox" name="salary_head_ids[]" value="{{ $salaryHead->id }}"
                                    class="salary-head-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                @endif
                            </td>

                            <!-- Staff ID Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-id-badge text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $salaryHead->staff_id }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Name Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-user text-white text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $salaryHead->user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $salaryHead->user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Role Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $salaryHead->role_name }}
                                </span>
                            </td>

                            <!-- Salary Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-lg font-bold text-green-600">
                                    {{ $salaryHead->formatted_salary_amount }}
                                </div>
                            </td>

                            <!-- Status Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $salaryHead->status_badge_class }}">
                                    {{ $salaryHead->status_display }}
                                </span>
                            </td>

                            <!-- Actions Column -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center space-x-2">
                                    <!-- View Button -->
                                    <a href="{{ route('admin.salary-heads.show', $salaryHead->id) }}" id="viewBtn{{ $salaryHead->id }}"
                                        class="p-2 rounded-lg text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition-all duration-300"
                                        title="View Salary Head">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                        </svg>
                                    </a>

                                    @if($salaryHead->canBeApproved())
                                    <!-- Approve Button -->
                                    <button onclick="approveSalaryHead({{ $salaryHead->id }})" id="approveBtn{{ $salaryHead->id }}"
                                        class="p-2 rounded-lg text-green-600 bg-green-50 hover:bg-green-100 border border-green-200 transition-all duration-300"
                                        title="Approve Salary Head">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>

                                    <!-- Reject Button -->
                                    <button onclick="rejectSalaryHead({{ $salaryHead->id }})" id="rejectBtn{{ $salaryHead->id }}"
                                        class="p-2 rounded-lg text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 transition-all duration-300"
                                        title="Reject Salary Head">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    @elseif($salaryHead->canBeEdited())
                                    <!-- Edit Button -->
                                    <a href="{{ route('admin.salary-heads.edit', $salaryHead->id) }}" id="editBtn{{ $salaryHead->id }}"
                                        class="p-2 rounded-lg text-yellow-600 bg-yellow-50 hover:bg-yellow-100 border border-yellow-200 transition-all duration-300"
                                        title="Edit Salary Head">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                    </a>
                                    @endif

                                    @if($salaryHead->canBeDeleted())
                                    <!-- Delete Button -->
                                    <form action="{{ route('admin.salary-heads.destroy', $salaryHead->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this salary head?');"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" id="deleteBtn{{ $salaryHead->id }}"
                                            class="p-2 rounded-lg text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 transition-all duration-300"
                                            title="Delete Salary Head">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-users text-gray-400 text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No salary heads found</h3>
                                <p class="text-gray-500">Try adjusting your search filters or create a new salary head.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if($salaryHeads->hasPages())
        <div class="mt-6 flex justify-center">
            <div class="bg-white/90 backdrop-blur-sm rounded-lg shadow-lg border border-white/20 p-2">
                {{ $salaryHeads->appends(request()->query())->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Rejection Modal --}}
<div id="rejectionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-times text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Reject Salary Head</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">Please provide a reason for rejecting this salary head:</p>
                <textarea id="rejectionReason" rows="4"
                    class="mt-3 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                    placeholder="Enter rejection reason..."></textarea>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirmReject"
                    class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Reject
                </button>
                <button id="cancelReject"
                    class="mt-3 px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* Custom scrollbar */
    .overflow-y-auto::-webkit-scrollbar {
        width: 6px;
    }

    .overflow-y-auto::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Backdrop blur fallback */
    .backdrop-blur-sm {
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }

    /* Glassmorphism effect */
    .bg-white\/90 {
        background: rgba(255, 255, 255, 0.9);
    }

    .bg-white\/80 {
        background: rgba(255, 255, 255, 0.8);
    }

    /* Mobile responsive improvements */
    @media (max-width: 640px) {
        .p-2 {
            padding: 0.25rem;
        }

        .space-y-3>*+* {
            margin-top: 0.75rem;
        }
    }

    /* Ensure proper responsive behavior */
    @media (max-width: 1023px) {
        .lg\:hidden {
            display: block !important;
        }

        .lg\:block {
            display: none !important;
        }
    }

    @media (min-width: 1024px) {
        .lg\:hidden {
            display: none !important;
        }

        .lg\:block {
            display: block !important;
        }
    }

    /* Button hover effects */
    .group:hover .group-hover\:translate-x-full {
        transform: translateX(100%);
    }

    .group:hover .group-hover\:rotate-90 {
        transform: rotate(90deg);
    }

    .group:hover .group-hover\:rotate-12 {
        transform: rotate(12deg);
    }

    /* Loading states */
    .fa-spin {
        animation: fa-spin 2s infinite linear;
    }

    @keyframes fa-spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Enhanced focus states */
    .focus\:ring-2:focus {
        outline: 2px solid transparent;
        outline-offset: 2px;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
    }

    /* Smooth transitions */
    * {
        transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 150ms;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let currentSalaryHeadId = null;

    $(document).ready(function() {
        // Enhanced button loading states
        $('#createSalaryHeadBtn').on('click', function() {
            const icon = $('#defaultPlusIcon');
            const spinner = $('#spinnerIcon');
            const text = $('#buttonText');

            icon.addClass('hidden');
            spinner.removeClass('hidden');
            text.text('Loading...');
        });

        $('#refreshBtn').on('click', function() {
            const refreshIcon = $('#refreshIcon');
            const spinnerIcon = $('#refreshSpinnerIcon');
            const buttonText = $('#refreshButtonText');

            refreshIcon.addClass('hidden');
            spinnerIcon.removeClass('hidden');
            buttonText.text('Refreshing...');
        });

        $('#searchBtn').on('click', function() {
            const btn = $(this);
            const originalHtml = btn.html();

            btn.html('<i class="fas fa-spinner fa-spin mr-1"></i><span class="hidden sm:inline">Searching...</span>');
            btn.prop('disabled', true);
        });

        // Auto-hide success/error messages
        setTimeout(function() {
            $('.bg-gradient-to-r.from-green-50, .bg-gradient-to-r.from-red-50').fadeOut('slow');
        }, 5000);

        // Enhanced search functionality with debounce
        let searchTimeout;
        $('#search').on('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val();

            if (searchTerm.length > 2) {
                searchTimeout = setTimeout(() => {
                    $(this).closest('form').submit();
                }, 1000);
            } else if (searchTerm.length === 0) {
                searchTimeout = setTimeout(() => {
                    $(this).closest('form').submit();
                }, 500);
            }
        });

        // Auto-submit on filter changes
        $('select[name="role_name"], select[name="status"]').on('change', function() {
            $(this).closest('form').submit();
        });

        // Select all functionality
        $('#selectAll').on('change', function() {
            $('.salary-head-checkbox').prop('checked', this.checked);
            updateBulkApproveButton();
        });

        $('.salary-head-checkbox').on('change', function() {
            updateBulkApproveButton();

            // Update select all checkbox
            const totalCheckboxes = $('.salary-head-checkbox').length;
            const checkedCheckboxes = $('.salary-head-checkbox:checked').length;

            $('#selectAll').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
            $('#selectAll').prop('checked', checkedCheckboxes === totalCheckboxes);
        });

        // Keyboard shortcuts
        $(document).keydown(function(e) {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 75) {
                e.preventDefault();
                $('#search').focus();
            }
        });

        // Button click handlers for individual salary head actions
        $('[id^="viewBtn"]').on('click', function() {
            const btn = $(this);
            btn.html('<i class="fas fa-spinner fa-spin"></i>');
            btn.prop('disabled', true);
        });

        $('[id^="editBtn"]').on('click', function() {
            const btn = $(this);
            btn.html('<i class="fas fa-spinner fa-spin"></i>');
            btn.prop('disabled', true);
        });

        $('[id^="deleteBtn"]').on('click', function(e) {
            if (!confirm('Are you sure you want to delete this salary head?')) {
                e.preventDefault();
                return false;
            }

            const btn = $(this);
            btn.html('<i class="fas fa-spinner fa-spin"></i>');
            btn.prop('disabled', true);
        });

        // Rejection modal handlers
        $('#cancelReject').on('click', function() {
            $('#rejectionModal').addClass('hidden');
            $('#rejectionReason').val('');
            currentSalaryHeadId = null;
        });

        $('#confirmReject').on('click', function() {
            const reason = $('#rejectionReason').val().trim();

            if (!reason) {
                alert('Please provide a rejection reason.');
                return;
            }

            if (currentSalaryHeadId) {
                rejectSalaryHeadConfirm(currentSalaryHeadId, reason);
            }
        });

        // Close modal when clicking outside
        $('#rejectionModal').on('click', function(e) {
            if (e.target === this) {
                $('#rejectionModal').addClass('hidden');
                $('#rejectionReason').val('');
                currentSalaryHeadId = null;
            }
        });

        // Responsive table handling
        function handleResponsiveTable() {
            const screenWidth = window.innerWidth;
            const mobileCards = document.querySelector('.lg\\:hidden');
            const desktopTable = document.querySelector('.lg\\:block');

            if (screenWidth < 1024) {
                if (mobileCards) mobileCards.style.display = 'block';
                if (desktopTable) desktopTable.style.display = 'none';
            } else {
                if (mobileCards) mobileCards.style.display = 'none';
                if (desktopTable) desktopTable.style.display = 'block';
            }
        }

        // Handle window resize
        $(window).on('resize', handleResponsiveTable);

        // Initial call
        handleResponsiveTable();

        // Touch-friendly interactions for mobile
        if ('ontouchstart' in window) {
            $('.hover\\:scale-105').addClass('active:scale-95');
        }

        // Smooth scrolling for mobile
        if (window.innerWidth < 768) {
            $('html').css({
                'scroll-behavior': 'smooth',
                '-webkit-overflow-scrolling': 'touch'
            });
        }

        // Initial bulk approve button state
        updateBulkApproveButton();
    });

    function updateBulkApproveButton() {
        const checkedCount = $('.salary-head-checkbox:checked').length;
        const bulkApproveBtn = $('#bulkApproveBtn');

        if (checkedCount > 0) {
            bulkApproveBtn.removeClass('opacity-50 cursor-not-allowed').prop('disabled', false);
            $('#bulkApproveButtonText').text(`Bulk Approve (${checkedCount})`);
        } else {
            bulkApproveBtn.addClass('opacity-50 cursor-not-allowed').prop('disabled', true);
            $('#bulkApproveButtonText').text('Bulk Approve');
        }
    }

    function approveSalaryHead(id) {
        if (!confirm('Are you sure you want to approve this salary head?')) {
            return;
        }

        const btn = $(`#approveBtn${id}`);
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

        $.ajax({
            url: `/admin/salary-heads/${id}/approve`,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message || 'Failed to approve salary head.');
                    btn.html(originalHtml).prop('disabled', false);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                alert(response?.error || 'Failed to approve salary head.');
                btn.html(originalHtml).prop('disabled', false);
            }
        });
    }

    function rejectSalaryHead(id) {
        currentSalaryHeadId = id;
        $('#rejectionModal').removeClass('hidden');
        $('#rejectionReason').focus();
    }

    function rejectSalaryHeadConfirm(id, reason) {
        const btn = $(`#rejectBtn${id}`);
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

        $.ajax({
            url: `/admin/salary-heads/${id}/reject`,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                rejection_reason: reason
            },
            success: function(response) {
                if (response.success) {
                    $('#rejectionModal').addClass('hidden');
                    $('#rejectionReason').val('');
                    currentSalaryHeadId = null;
                    location.reload();
                } else {
                    alert(response.message || 'Failed to reject salary head.');
                    btn.html(originalHtml).prop('disabled', false);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                alert(response?.error || 'Failed to reject salary head.');
                btn.html(originalHtml).prop('disabled', false);
            }
        });
    }

    function bulkApprove() {
        const checkedIds = $('.salary-head-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (checkedIds.length === 0) {
            alert('Please select at least one salary head to approve.');
            return;
        }

        if (!confirm(`Are you sure you want to approve ${checkedIds.length} salary head(s)?`)) {
            return;
        }

        const bulkApproveBtn = $('#bulkApproveBtn');
        const bulkApproveIcon = $('#bulkApproveIcon');
        const bulkApproveSpinner = $('#bulkApproveSpinnerIcon');
        const bulkApproveText = $('#bulkApproveButtonText');

        // Show loading state
        bulkApproveIcon.addClass('hidden');
        bulkApproveSpinner.removeClass('hidden');
        bulkApproveText.text('Processing...');
        bulkApproveBtn.prop('disabled', true);

        $.ajax({
            url: '/admin/salary-heads/bulk-approve',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                salary_head_ids: checkedIds
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message || 'Failed to process bulk approval.');
                    // Reset button state
                    bulkApproveIcon.removeClass('hidden');
                    bulkApproveSpinner.addClass('hidden');
                    bulkApproveText.text('Bulk Approve');
                    bulkApproveBtn.prop('disabled', false);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                alert(response?.error || 'Failed to process bulk approval.');
                // Reset button state
                bulkApproveIcon.removeClass('hidden');
                bulkApproveSpinner.addClass('hidden');
                bulkApproveText.text('Bulk Approve');
                bulkApproveBtn.prop('disabled', false);
            }
        });
    }
</script>
@endpush
@endsection