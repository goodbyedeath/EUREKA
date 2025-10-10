<!-- Quick Navigation Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-8">
    <!-- User Management -->
    <a href="{{ route('admin.users') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 {{ request()->routeIs('admin.users') ? 'ring-2 ring-blue-500 border-blue-500' : '' }}">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <i class="fas fa-users text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">User Management</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage users and permissions</p>
            </div>
        </div>
    </a>

    <!-- Quest Locations -->
    <a href="{{ route('admin.quest-locations') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-green-300 dark:hover:border-green-600 {{ request()->routeIs('admin.quest-locations') ? 'ring-2 ring-green-500 border-green-500' : '' }}">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <i class="fas fa-map-marker-alt text-green-600 dark:text-green-400 text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">Quest Locations</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage GPS quest locations</p>
            </div>
        </div>
    </a>

    <!-- Game Management -->
    <a href="{{ route('admin.games') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600 {{ request()->routeIs('admin.games') ? 'ring-2 ring-purple-500 border-purple-500' : '' }}">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <i class="fas fa-gamepad text-purple-600 dark:text-purple-400 text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">Game Management</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage outdoor games</p>
            </div>
        </div>
    </a>

    <!-- Feature Management -->
    <a href="{{ route('admin.feature-management') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-orange-300 dark:hover:border-orange-600 {{ request()->routeIs('admin.feature-management') ? 'ring-2 ring-orange-500 border-orange-500' : '' }}">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <i class="fas fa-toggle-on text-orange-600 dark:text-orange-400 text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">Feature Control</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Toggle user features on/off</p>
            </div>
        </div>
    </a>

    <!-- Team Management -->
    <a href="{{ route('admin.team-management') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600 {{ request()->routeIs('admin.team-management') ? 'ring-2 ring-indigo-500 border-indigo-500' : '' }}">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <i class="fas fa-users-cog text-indigo-600 dark:text-indigo-400 text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Team Management</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage teams and members</p>
            </div>
        </div>
    </a>

    <!-- User Progress -->
    <a href="{{ route('admin.user-progress') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-cyan-300 dark:hover:border-cyan-600 {{ request()->routeIs('admin.user-progress') ? 'ring-2 ring-cyan-500 border-cyan-500' : '' }}">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <i class="fas fa-chart-line text-cyan-600 dark:text-cyan-400 text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">User Progress</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Track user performance</p>
            </div>
        </div>
    </a>

    <!-- Hero Slides -->
    <a href="{{ route('admin.hero-slides') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-pink-300 dark:hover:border-pink-600 {{ request()->routeIs('admin.hero-slides') ? 'ring-2 ring-pink-500 border-pink-500' : '' }}">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-pink-100 dark:bg-pink-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <i class="fas fa-images text-pink-600 dark:text-pink-400 text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-pink-600 dark:group-hover:text-pink-400 transition-colors">Hero Slides</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage homepage slides</p>
            </div>
        </div>
    </a>


    <!-- Questionnaires Manager -->
    <a href="{{ route('admin.dashboard-management') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-emerald-300 dark:hover:border-emerald-600 {{ request()->routeIs('admin.dashboard-management') ? 'ring-2 ring-emerald-500 border-emerald-500' : '' }}">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <i class="fas fa-cogs text-emerald-600 dark:text-emerald-400 text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Questionnaires Manager</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage questionnaires & assessments</p>
            </div>
        </div>
    </a>
</div>