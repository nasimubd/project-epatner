@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50">
    <div class="container mx-auto px-4 py-6 pb-20 sm:pb-6">
        <!-- Enhanced Header Section -->
        <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
            <!-- Header with Gradient Background -->
            <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 px-6 py-8">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">Staff Management</h1>
                        <p class="text-blue-100 text-sm sm:text-base">Manage your team members and their permissions</p>
                    </div>
                    <div class="w-full sm:w-auto">
                        <a href="{{ route('admin.staff.create') }}" id="addStaffBtn"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-white text-blue-700 font-semibold rounded-xl hover:bg-blue-50 transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl group">
                            <svg id="staffIcon" class="w-5 h-5 mr-2 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            <svg id="staffSpinner" class="hidden w-5 h-5 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span id="staffButtonText">Add New Staff</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards (Mobile Responsive) -->
            <div class="bg-gray-50 px-6 py-4 border-b">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl p-4 text-center shadow-sm">
                        <div class="text-2xl font-bold text-blue-600">{{ $totalStaff ?? $staff->total() }}</div>
                        <div class="text-xs text-gray-500 mt-1">Total Staff</div>
                    </div>
                    <div class="bg-white rounded-xl p-4 text-center shadow-sm">
                        <div class="text-2xl font-bold text-green-600">{{ $activeStaff ?? $staff->where('is_active', true)->count() }}</div>
                        <div class="text-xs text-gray-500 mt-1">Active</div>
                    </div>
                    <div class="bg-white rounded-xl p-4 text-center shadow-sm">
                        <div class="text-2xl font-bold text-red-600">{{ $inactiveStaff ?? $staff->where('is_active', false)->count() }}</div>
                        <div class="text-xs text-gray-500 mt-1">Inactive</div>
                    </div>
                    <div class="bg-white rounded-xl p-4 text-center shadow-sm">
                        <div class="text-2xl font-bold text-purple-600">{{ $uniqueCategoriesCount ?? 0 }}</div>
                        <div class="text-xs text-gray-500 mt-1">Categories</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        @if(session('success'))
        <div class="mt-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-r-xl shadow-md animate-fade-in">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Mobile Card Layout -->
        <div class="mt-6 space-y-4 lg:hidden">
            @forelse($staff as $member)
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <!-- Card Header -->
                <div class="bg-gradient-to-r from-gray-50 to-blue-50 px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <!-- Avatar -->
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg">
                                <span class="text-white font-bold text-lg">
                                    {{ strtoupper(substr($member->user->name, 0, 1)) }}
                                </span>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 text-lg">{{ $member->user->name }}</h3>
                                <p class="text-sm text-gray-500">{{ ucfirst($member->user->roles->first()->name ?? 'staff') }}</p>
                            </div>
                        </div>
                        <!-- Status Badge -->
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $member->is_active ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                            <span class="w-2 h-2 rounded-full {{ $member->is_active ? 'bg-green-400' : 'bg-red-400' }} mr-2"></span>
                            {{ $member->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <!-- Card Content -->
                <div class="p-6 space-y-4">
                    <!-- Contact Info -->
                    <div class="space-y-2">
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            {{ $member->user->email }}
                        </div>
                        @if($member->phone)
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            {{ $member->phone }}
                        </div>
                        @endif
                    </div>

                    <!-- DSR Assignments (Mobile) -->
                    @if($member->user->roles->first()->name === 'dsr' && $member->assignedStaffMembers->count() > 0)
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Assigned Staff</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($member->assignedStaffMembers->take(2) as $assignedStaff)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                                {{ $assignedStaff->user->name }}
                            </span>
                            @endforeach
                            @if($member->assignedStaffMembers->count() > 2)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                +{{ $member->assignedStaffMembers->count() - 2 }} more
                            </span>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Categories -->
                    @if($member->productCategories->count() > 0)
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Assigned Categories</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($member->productCategories->take(3) as $category)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                {{ $category->name }}
                            </span>
                            @endforeach
                            @if($member->productCategories->count() > 3)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                +{{ $member->productCategories->count() - 3 }} more
                            </span>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Ledgers -->
                    @if($member->ledgers->count() > 0)
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Assigned Ledgers</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($member->ledgers->take(2) as $ledger)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                {{ $ledger->name }}
                            </span>
                            @endforeach
                            @if($member->ledgers->count() > 2)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                +{{ $member->ledgers->count() - 2 }} more
                            </span>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Card Actions -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-100">
                    <div class="flex justify-between items-center">
                        <div class="flex space-x-2">
                            <!-- Edit Button -->
                            <a href="{{ route('admin.staff.edit', $member->id) }}" id="editBtn{{ $member->id }}"
                                class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-all duration-200 transform hover:scale-105 shadow-sm">
                                <svg id="editIcon{{ $member->id }}" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <svg id="editSpinner{{ $member->id }}" class="hidden w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span id="editText{{ $member->id }}">Edit</span>
                            </a>

                            <!-- Toggle Status Button -->
                            <button onclick="toggleStatus('{{ $member->id }}')" id="toggleBtn{{ $member->id }}"
                                class="inline-flex items-center px-3 py-2 {{ $member->is_active ? 'bg-orange-600 hover:bg-orange-700' : 'bg-green-600 hover:bg-green-700' }} text-white text-sm font-medium rounded-lg transition-all duration-200 transform hover:scale-105 shadow-sm">
                                <svg id="toggleIcon{{ $member->id }}" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($member->is_active)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
                                    @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    @endif
                                </svg>
                                <svg id="toggleSpinner{{ $member->id }}" class="hidden w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span id="toggleText{{ $member->id }}">{{ $member->is_active ? 'Deactivate' : 'Activate' }}</span>
                            </button>
                        </div>

                        <!-- Delete Button -->
                        <form action="{{ route('admin.staff.destroy', $member->id) }}" method="POST" class="inline"
                            onsubmit="return confirm('Are you sure you want to delete this staff member? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" id="deleteBtn{{ $member->id }}"
                                class="inline-flex items-center px-3 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-all duration-200 transform hover:scale-105 shadow-sm">
                                <svg id="deleteIcon{{ $member->id }}" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <svg id="deleteSpinner{{ $member->id }}" class="hidden w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span id="deleteText{{ $member->id }}">Delete</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="text-center py-12">
                    <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No Staff Members Found</h3>
                    <p class="text-gray-500 mb-6">Get started by adding your first team member.</p>
                    <a href="{{ route('admin.staff.create') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add First Staff Member
                    </a>
                </div>
            </div>
            @endforelse
        </div>

        <!-- Desktop Table Layout -->
        <div class="mt-6 bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100 hidden lg:block">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-gray-50 to-blue-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Staff Member</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role & Contact</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">DSR Assignments</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Assigned Categories</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Assigned Ledgers</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($staff as $member)
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <!-- Staff Member -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <!-- Avatar -->
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg mr-4">
                                        <span class="text-white font-bold text-lg">
                                            {{ strtoupper(substr($member->user->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">{{ $member->user->name }}</div>
                                        <div class="text-sm text-gray-500">ID: #{{ str_pad($member->id, 4, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Role & Contact -->
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                                            {{ ucfirst($member->user->roles->first()->name ?? 'staff') }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        {{ $member->user->email }}
                                    </div>
                                    @if($member->phone)
                                    <div class="text-sm text-gray-600 flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        {{ $member->phone }}
                                    </div>
                                    @else
                                    <div class="text-sm text-gray-400 italic">No phone number</div>
                                    @endif
                                </div>
                            </td>

                            <!-- DSR Assignments -->
                            <td class="px-6 py-4">
                                @if($member->user->roles->first()->name === 'dsr' && $member->assignedStaffMembers->count() > 0)
                                <div class="flex flex-wrap gap-1">
                                    @foreach($member->assignedStaffMembers->take(2) as $assignedStaff)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                                        {{ Str::limit($assignedStaff->user->name, 12) }}
                                    </span>
                                    @endforeach
                                    @if($member->assignedStaffMembers->count() > 2)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200"
                                        title="{{ $member->assignedStaffMembers->skip(2)->pluck('user.name')->join(', ') }}">
                                        +{{ $member->assignedStaffMembers->count() - 2 }}
                                    </span>
                                    @endif
                                </div>
                                @elseif($member->user->roles->first()->name === 'dsr')
                                <span class="text-sm text-orange-500 italic">No staff assigned</span>
                                @else
                                <span class="text-sm text-gray-400 italic">Not a DSR</span>
                                @endif
                            </td>

                            <!-- Categories -->
                            <td class="px-6 py-4">
                                @if($member->productCategories->count() > 0)
                                <div class="flex flex-wrap gap-1">
                                    @foreach($member->productCategories->take(3) as $category)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                        {{ $category->name }}
                                    </span>
                                    @endforeach
                                    @if($member->productCategories->count() > 3)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200"
                                        title="{{ $member->productCategories->skip(3)->pluck('name')->join(', ') }}">
                                        +{{ $member->productCategories->count() - 3 }}
                                    </span>
                                    @endif
                                </div>
                                @else
                                <span class="text-sm text-gray-400 italic">No categories assigned</span>
                                @endif
                            </td>

                            <!-- Ledgers -->
                            <td class="px-6 py-4">
                                @if($member->ledgers->count() > 0)
                                <div class="flex flex-wrap gap-1">
                                    @foreach($member->ledgers->take(2) as $ledger)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                        {{ Str::limit($ledger->name, 15) }}
                                    </span>
                                    @endforeach
                                    @if($member->ledgers->count() > 2)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200"
                                        title="{{ $member->ledgers->skip(2)->pluck('name')->join(', ') }}">
                                        +{{ $member->ledgers->count() - 2 }}
                                    </span>
                                    @endif
                                </div>
                                @else
                                <span class="text-sm text-gray-400 italic">No ledgers assigned</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $member->is_active ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                                    <span class="w-2 h-2 rounded-full {{ $member->is_active ? 'bg-green-400' : 'bg-red-400' }} mr-2"></span>
                                    {{ $member->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center space-x-2">
                                    <!-- Edit Button -->
                                    <a href="{{ route('admin.staff.edit', $member->id) }}" id="editBtn{{ $member->id }}"
                                        class="inline-flex items-center p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all duration-200 transform hover:scale-105 shadow-sm group"
                                        title="Edit Staff">
                                        <svg id="editIcon{{ $member->id }}" class="w-4 h-4 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <svg id="editSpinner{{ $member->id }}" class="hidden w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </a>

                                    <!-- Toggle Status Button -->
                                    <button onclick="toggleStatus('{{ $member->id }}')" id="toggleBtn{{ $member->id }}"
                                        class="inline-flex items-center p-2 {{ $member->is_active ? 'bg-orange-100 text-orange-600 hover:bg-orange-200' : 'bg-green-100 text-green-600 hover:bg-green-200' }} rounded-lg transition-all duration-200 transform hover:scale-105 shadow-sm group"
                                        title="{{ $member->is_active ? 'Deactivate Staff' : 'Activate Staff' }}">
                                        <svg id="toggleIcon{{ $member->id }}" class="w-4 h-4 group-hover:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($member->is_active)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
                                            @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            @endif
                                        </svg>
                                        <svg id="toggleSpinner{{ $member->id }}" class="hidden w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </button>

                                    <!-- Delete Button -->
                                    <form action="{{ route('admin.staff.destroy', $member->id) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Are you sure you want to delete this staff member? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" id="deleteBtn{{ $member->id }}"
                                            class="inline-flex items-center p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all duration-200 transform hover:scale-105 shadow-sm group"
                                            title="Delete Staff">
                                            <svg id="deleteIcon{{ $member->id }}" class="w-4 h-4 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <svg id="deleteSpinner{{ $member->id }}" class="hidden w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Staff Members Found</h3>
                                <p class="text-gray-500 mb-6">Get started by adding your first team member.</p>
                                <a href="{{ route('admin.staff.create') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Add First Staff Member
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($staff->hasPages())
        <div class="mt-6 bg-white rounded-xl shadow-lg border border-gray-100 p-4">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                <div class="text-sm text-gray-700">
                    Showing {{ $staff->firstItem() }} to {{ $staff->lastItem() }} of {{ $staff->total() }} results
                </div>
                <div class="flex justify-center">
                    {{ $staff->links('pagination::tailwind') }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    .animate-fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .shadow-left {
        box-shadow: inset 10px 0 10px -10px rgba(0, 0, 0, 0.1);
    }

    .shadow-right {
        box-shadow: inset -10px 0 10px -10px rgba(0, 0, 0, 0.1);
    }

    /* Custom scrollbar for webkit browsers */
    .overflow-x-auto::-webkit-scrollbar {
        height: 6px;
    }

    .overflow-x-auto::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }

    .overflow-x-auto::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .overflow-x-auto::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Tooltip styles */
    [title]:hover::after {
        content: attr(title);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 1000;
        pointer-events: none;
    }

    /* Responsive table improvements */
    @media (max-width: 1024px) {
        .overflow-x-auto {
            -webkit-overflow-scrolling: touch;
        }
    }

    /* Loading state styles */
    .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    /* Enhanced button hover effects */
    .group:hover .group-hover\:rotate-12 {
        transform: rotate(12deg);
    }

    .group:hover .group-hover\:rotate-180 {
        transform: rotate(180deg);
    }

    /* Custom pagination styles */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
    }

    .pagination a,
    .pagination span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.5rem;
        height: 2.5rem;
        padding: 0.5rem;
        text-decoration: none;
        border-radius: 0.5rem;
        transition: all 0.2s;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .pagination a {
        background: white;
        color: #6b7280;
        border: 1px solid #d1d5db;
    }

    .pagination a:hover {
        background: #f3f4f6;
        color: #374151;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .pagination .active span {
        background: #3b82f6;
        color: white;
        border: 1px solid #3b82f6;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
    }

    .pagination .disabled span {
        background: #f9fafb;
        color: #d1d5db;
        border: 1px solid #e5e7eb;
        cursor: not-allowed;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function toggleStatus(staffId) {
        if (confirm('Are you sure you want to change this staff member\'s status?')) {
            // Show loading state
            const toggleBtn = document.getElementById(`toggleBtn${staffId}`);
            const toggleIcon = document.getElementById(`toggleIcon${staffId}`);
            const toggleSpinner = document.getElementById(`toggleSpinner${staffId}`);

            toggleIcon.classList.add('hidden');
            toggleSpinner.classList.remove('hidden');
            toggleBtn.disabled = true;
            toggleBtn.classList.add('opacity-50', 'cursor-not-allowed');

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/staff/${staffId}/toggle-status`;

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'PATCH';

            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            form.submit();
        }
    }

    $(document).ready(function() {
        // Add loading states for main buttons
        $('#addStaffBtn').on('click', function() {
            $('#staffIcon').addClass('hidden');
            $('#staffSpinner').removeClass('hidden');
            $('#staffButtonText').text('Loading...');
            $(this).addClass('opacity-75 cursor-not-allowed').prop('disabled', true);
        });

        // Add loading states for edit buttons
        $('[id^="editBtn"]').on('click', function() {
            const id = this.id.replace('editBtn', '');
            $(`#editIcon${id}`).addClass('hidden');
            $(`#editSpinner${id}`).removeClass('hidden');
            $(`#editText${id}`).text('Loading...');
            $(this).addClass('opacity-75 cursor-not-allowed').prop('disabled', true);
        });

        // Add loading states for delete buttons
        $('[id^="deleteBtn"]').on('click', function(e) {
            const id = this.id.replace('deleteBtn', '');
            const form = $(this).closest('form');

            // Prevent default form submission temporarily
            e.preventDefault();

            if (confirm('Are you sure you want to delete this staff member? This action cannot be undone.')) {
                $(`#deleteIcon${id}`).addClass('hidden');
                $(`#deleteSpinner${id}`).removeClass('hidden');
                $(`#deleteText${id}`).text('Deleting...');
                $(this).addClass('opacity-75 cursor-not-allowed').prop('disabled', true);

                // Submit the form after showing loading state
                setTimeout(() => {
                    form.submit();
                }, 100);
            }
        });

        // Add loading states for pagination links
        $('.pagination a').on('click', function() {
            $(this).addClass('opacity-50 pointer-events-none');
            $(this).append('<svg class="animate-spin ml-2 h-4 w-4 text-current inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>');
        });

        // Add tooltip functionality for truncated text
        $('[title]').each(function() {
            $(this).attr('data-toggle', 'tooltip');
        });

        // Add search functionality (if you want to add client-side filtering)
        let searchTimeout;
        $('#staffSearch').on('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val().toLowerCase();

            searchTimeout = setTimeout(() => {
                $('.staff-card, tbody tr').each(function() {
                    const staffName = $(this).find('.staff-name, .text-sm.font-semibold').text().toLowerCase();
                    const staffEmail = $(this).find('.staff-email, .text-sm.text-gray-600').text().toLowerCase();

                    if (staffName.includes(searchTerm) || staffEmail.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }, 300);
        });

        // Add keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + N to add new staff
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                window.location.href = '{{ route("admin.staff.create") }}';
            }
        });

        // Add smooth animations for dynamic content
        $('.staff-card, tbody tr').each(function(index) {
            $(this).css({
                'animation-delay': (index * 50) + 'ms',
                'animation-fill-mode': 'both'
            }).addClass('animate-fade-in');
        });

        // Add responsive table scroll indicator
        const tableContainer = $('.overflow-x-auto');
        if (tableContainer.length) {
            tableContainer.on('scroll', function() {
                const scrollLeft = $(this).scrollLeft();
                const scrollWidth = this.scrollWidth;
                const clientWidth = this.clientWidth;

                // Add shadow indicators for horizontal scroll
                if (scrollLeft > 0) {
                    $(this).addClass('shadow-left');
                } else {
                    $(this).removeClass('shadow-left');
                }

                if (scrollLeft < scrollWidth - clientWidth - 1) {
                    $(this).addClass('shadow-right');
                } else {
                    $(this).removeClass('shadow-right');
                }
            });

            // Trigger initial scroll check
            tableContainer.trigger('scroll');
        }

        // Add click-to-copy functionality for email addresses
        $('.staff-email, .text-sm.text-gray-600').filter(':contains("@")').on('click', function(e) {
            e.preventDefault();
            const email = $(this).text().trim();

            if (email.includes('@')) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(email).then(() => {
                        showToast('Email copied to clipboard!', 'success');
                    });
                } else {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = email;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    showToast('Email copied to clipboard!', 'success');
                }
            }
        });

        // Toast notification function
        function showToast(message, type = 'info') {
            const toast = $(`
                <div class="fixed top-4 right-4 z-50 bg-white border border-gray-200 rounded-lg shadow-lg p-4 transform translate-x-full transition-transform duration-300">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">${message}</p>
                        </div>
                    </div>
                </div>
            `);

            $('body').append(toast);

            setTimeout(() => {
                toast.removeClass('translate-x-full');
            }, 100);

            setTimeout(() => {
                toast.addClass('translate-x-full');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }

        // Add performance monitoring (optional)
        if ('performance' in window) {
            window.addEventListener('load', function() {
                setTimeout(() => {
                    const perfData = performance.getEntriesByType('navigation')[0];
                    console.log('Page load time:', perfData.loadEventEnd - perfData.loadEventStart, 'ms');
                }, 0);
            });
        }

        // Auto-hide success messages
        setTimeout(() => {
            $('.bg-green-50').fadeOut('slow');
        }, 5000);

        // Add loading state to forms
        $('form').on('submit', function() {
            $(this).addClass('loading');
            $(this).find('button[type="submit"]').prop('disabled', true);
        });

        // Prevent double-click submissions
        $('button[type="submit"]').on('click', function() {
            const $this = $(this);
            if ($this.data('clicked')) {
                return false;
            }
            $this.data('clicked', true);
            setTimeout(() => {
                $this.removeData('clicked');
            }, 2000);
        });
    });
</script>
@endpush
@endsection