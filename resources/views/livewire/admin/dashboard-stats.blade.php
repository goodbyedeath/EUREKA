<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
    <div class="bg-blue-100 p-4 sm:p-6 rounded-lg">
        <div class="text-xl sm:text-2xl font-bold text-blue-800">{{ $stats['total_questionnaires'] }}</div>
        <div class="text-sm sm:text-base text-blue-600">Total Questionnaires</div>
    </div>
    <div class="bg-green-100 p-4 sm:p-6 rounded-lg">
        <div class="text-xl sm:text-2xl font-bold text-green-800">{{ $stats['active_questionnaires'] }}</div>
        <div class="text-sm sm:text-base text-green-600">Active Questionnaires</div>
    </div>
    <div class="bg-purple-100 p-4 sm:p-6 rounded-lg">
        <div class="text-xl sm:text-2xl font-bold text-purple-800">{{ $stats['total_users'] }}</div>
        <div class="text-sm sm:text-base text-purple-600">Total Users</div>
    </div>
    <div class="bg-yellow-100 p-4 sm:p-6 rounded-lg">
        <div class="text-xl sm:text-2xl font-bold text-yellow-800">{{ $stats['total_quiz_attempts'] }}</div>
        <div class="text-sm sm:text-base text-yellow-600">Quiz Attempts</div>
    </div>
</div>