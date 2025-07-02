<div>
    <!-- Statistics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
        <div class="bg-blue-100 dark:bg-blue-900/20 p-4 sm:p-6 rounded-lg">
            <div class="text-xl sm:text-2xl font-bold text-blue-800 dark:text-blue-400">{{ $stats['total_questionnaires'] }}</div>
            <div class="text-sm sm:text-base text-blue-600 dark:text-blue-300">Total Questionnaires</div>
        </div>
        <div class="bg-green-100 dark:bg-green-900/20 p-4 sm:p-6 rounded-lg">
            <div class="text-xl sm:text-2xl font-bold text-green-800 dark:text-green-400">{{ $stats['active_questionnaires'] }}</div>
            <div class="text-sm sm:text-base text-green-600 dark:text-green-300">Active Questionnaires</div>
        </div>
        <div class="bg-purple-100 dark:bg-purple-900/20 p-4 sm:p-6 rounded-lg">
            <div class="text-xl sm:text-2xl font-bold text-purple-800 dark:text-purple-400">{{ $stats['total_users'] }}</div>
            <div class="text-sm sm:text-base text-purple-600 dark:text-purple-300">Total Users</div>
        </div>
        <div class="bg-yellow-100 dark:bg-yellow-900/20 p-4 sm:p-6 rounded-lg">
            <div class="text-xl sm:text-2xl font-bold text-yellow-800 dark:text-yellow-400">{{ $stats['total_quiz_attempts'] }}</div>
            <div class="text-sm sm:text-base text-yellow-600 dark:text-yellow-300">Quiz Attempts</div>
        </div>
    </div>

    <!-- Quick Navigation Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-8">
    <!-- User Management -->
    <a href="{{ route('admin.users') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600">
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
    <a href="{{ route('admin.quest-locations') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-green-300 dark:hover:border-green-600">
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
    <a href="{{ route('admin.games') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600">
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
    <a href="{{ route('admin.feature-management') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-orange-300 dark:hover:border-orange-600">
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
    <a href="{{ route('admin.team-management') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600">
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
    <a href="{{ route('admin.user-progress') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-cyan-300 dark:hover:border-cyan-600">
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
    <a href="{{ route('admin.hero-slides') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-pink-300 dark:hover:border-pink-600">
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
    </div>
</div>