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