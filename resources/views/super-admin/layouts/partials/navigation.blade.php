<ul class="space-y-1">
    @role('super-admin')
    <!-- Dashboard -->
    <li>
        <a href="{{ route('super-admin.dashboard') }}"
            class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 touch-target
                   {{ request()->routeIs('super-admin.dashboard') 
                      ? 'bg-blue-100 text-blue-700 shadow-sm' 
                      : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
            <span class="mr-3 text-lg">📊</span>
            <span :class="$store.sidebarCollapsed ? 'md:hidden' : ''">Dashboard</span>
        </a>
    </li>

    <!-- Business Management Dropdown -->
    <li class="relative">
        <button @click="businessOpen = !businessOpen"
            class="group w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 touch-target
                       {{ request()->routeIs('super-admin.businesses.*') || request()->routeIs('super-admin.admins.*') 
                          ? 'bg-blue-100 text-blue-700 shadow-sm' 
                          : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
            <div class="flex items-center">
                <span class="mr-3 text-lg">🏢</span>
                <span :class="$store.sidebarCollapsed ? 'md:hidden' : ''">Manage Business</span>
            </div>
            <svg class="w-4 h-4 transition-transform duration-200"
                :class="{'rotate-180': businessOpen, 'md:hidden': $store.sidebarCollapsed}"
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div x-show="businessOpen && !$store.sidebarCollapsed"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="mt-2 space-y-1 pl-6">
            <a href="{{ route('super-admin.businesses.index') }}"
                class="group flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 touch-target
                       {{ request()->routeIs('super-admin.businesses.*') 
                          ? 'bg-blue-50 text-blue-600 font-medium' 
                          : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <span class="mr-3">📝</span>
                <span>Businesses</span>
            </a>
            <a href="{{ route('super-admin.admins.index') }}"
                class="group flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 touch-target
                       {{ request()->routeIs('super-admin.admins.*') 
                          ? 'bg-blue-50 text-blue-600 font-medium' 
                          : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <span class="mr-3">👥</span>
                <span>Business Admins</span>
            </a>
        </div>
    </li>

    <!-- Database Management Dropdown -->
    <li class="relative">
        <button @click="databaseOpen = !databaseOpen"
            class="group w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 touch-target
                       {{ request()->routeIs('super-admin.common-products.*') || 
                          request()->routeIs('super-admin.default-ledgers.*') || 
                          request()->routeIs('super-admin.customer-ledgers.*') || 
                          request()->routeIs('super-admin.common-categories.*') || 
                          request()->routeIs('super-admin.common-units.*') || 
                          request()->routeIs('super-admin.location-data.*') 
                          ? 'bg-blue-100 text-blue-700 shadow-sm' 
                          : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
            <div class="flex items-center">
                <span class="mr-3 text-lg">🗄️</span>
                <span :class="$store.sidebarCollapsed ? 'md:hidden' : ''">DB Management</span>
            </div>
            <svg class="w-4 h-4 transition-transform duration-200"
                :class="{'rotate-180': databaseOpen, 'md:hidden': $store.sidebarCollapsed}"
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div x-show="databaseOpen && !$store.sidebarCollapsed"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="mt-2 space-y-1 pl-6">
            <a href="{{ route('super-admin.common-categories.index') }}"
                class="group flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 touch-target
                       {{ request()->routeIs('super-admin.common-categories.*') 
                          ? 'bg-blue-50 text-blue-600 font-medium' 
                          : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <span class="mr-3">📁</span>
                <span>Common Categories</span>
            </a>
            <a href="{{ route('super-admin.common-units.index') }}"
                class="group flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 touch-target
                       {{ request()->routeIs('super-admin.common-units.*') 
                          ? 'bg-blue-50 text-blue-600 font-medium' 
                          : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <span class="mr-3">📏</span>
                <span>Common Units</span>
            </a>
            <a href="{{ route('super-admin.common-products.index') }}"
                class="group flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 touch-target
                       {{ request()->routeIs('super-admin.common-products.*') 
                          ? 'bg-blue-50 text-blue-600 font-medium' 
                          : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <span class="mr-3">🛒</span>
                <span>Common Products</span>
            </a>
            <a href="{{ route('super-admin.default-ledgers.index') }}"
                class="group flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 touch-target
                       {{ request()->routeIs('super-admin.default-ledgers.*') 
                          ? 'bg-blue-50 text-blue-600 font-medium' 
                          : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <span class="mr-3">📒</span>
                <span>Default Ledgers</span>
            </a>
            <a href="{{ route('super-admin.customer-ledgers.index') }}"
                class="group flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 touch-target
                       {{ request()->routeIs('super-admin.customer-ledgers.*') 
                          ? 'bg-blue-50 text-blue-600 font-medium' 
                          : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <span class="mr-3">👤</span>
                <span>Default Customers</span>
            </a>
            <a href="{{ route('super-admin.location-data.index') }}"
                class="group flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 touch-target
                       {{ request()->routeIs('super-admin.location-data.*') 
                          ? 'bg-blue-50 text-blue-600 font-medium' 
                          : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <span class="mr-3">🗺️</span>
                <span>Location Data</span>
            </a>
        </div>
    </li>

    <!-- Access Control Dropdown -->
    <li class="relative">
        <button @click="accessOpen = !accessOpen"
            class="group w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 touch-target
                       {{ request()->routeIs('roles.*') || request()->routeIs('permissions.*') 
                          ? 'bg-blue-100 text-blue-700 shadow-sm' 
                          : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
            <div class="flex items-center">
                <span class="mr-3 text-lg">🔐</span>
                <span :class="$store.sidebarCollapsed ? 'md:hidden' : ''">Access Control</span>
            </div>
            <svg class="w-4 h-4 transition-transform duration-200"
                :class="{'rotate-180': accessOpen, 'md:hidden': $store.sidebarCollapsed}"
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div x-show="accessOpen && !$store.sidebarCollapsed"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="mt-2 space-y-1 pl-6">
            <a href="{{ route('roles.index') }}"
                class="group flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 touch-target
                       {{ request()->routeIs('roles.*') 
                          ? 'bg-blue-50 text-blue-600 font-medium' 
                          : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <span class="mr-3">🔑</span>
                <span>Roles</span>
            </a>
            <a href="{{ route('permissions.index') }}"
                class="group flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 touch-target
                       {{ request()->routeIs('permissions.*') 
                          ? 'bg-blue-50 text-blue-600 font-medium' 
                          : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <span class="mr-3">🛡️</span>
                <span>Permissions</span>
            </a>
        </div>
    </li>

    <!-- Settings -->
    <li>
        <a href="{{ route('profile.edit') }}"
            class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 touch-target
                   {{ request()->routeIs('profile.*') 
                      ? 'bg-blue-100 text-blue-700 shadow-sm' 
                      : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
            <span class="mr-3 text-lg">⚙️</span>
            <span :class="$store.sidebarCollapsed ? 'md:hidden' : ''">Settings</span>
        </a>
    </li>

    <!-- Logout -->
    <li class="pt-4 border-t border-gray-200">
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit"
                class="group w-full flex items-center px-3 py-2 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-all duration-200 touch-target">
                <span class="mr-3 text-lg">🚪</span>
                <span :class="$store.sidebarCollapsed ? 'md:hidden' : ''">Logout</span>
            </button>
        </form>
    </li>
    @endrole
</ul>