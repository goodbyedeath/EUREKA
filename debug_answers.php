<?php
/**
 * Debug script for Multiple Choice Answer Issue
 * Run this with: php artisan tinker
 * Then copy-paste the relevant sections
 */

echo "=== MULTIPLE CHOICE ANSWER DEBUG SCRIPT ===\n\n";

echo "Run these commands in Laravel Tinker to debug the issue:\n\n";

echo "1. CHECK USER ANSWERS TABLE:\n";
echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";
echo "// Get the most recent quiz attempt\n";
echo "\$attempt = App\\Models\\QuizAttempt::latest()->first();\n";
echo "echo \"Attempt ID: \" . \$attempt->id;\n";
echo "echo \"Status: \" . \$attempt->status;\n\n";

echo "// Check user answers for this attempt\n";
echo "\$userAnswers = App\\Models\\UserAnswer::where('quiz_attempt_id', \$attempt->id)->get();\n";
echo "echo \"User answers count: \" . \$userAnswers->count();\n";
echo "foreach (\$userAnswers as \$answer) {\n";
echo "    echo \"Question {$answer->question_id}: '{$answer->answer}' - Correct: \" . (\$answer->is_correct ? 'YES' : 'NO');\n";
echo "}\n\n";

echo "2. CHECK SPECIFIC MULTIPLE CHOICE QUESTION:\n";
echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";
echo "// Find a multiple choice question\n";
echo "\$mcQuestion = App\\Models\\Question::where('type', 'multiple_choice')->first();\n";
echo "echo \"MC Question ID: \" . \$mcQuestion->id;\n";
echo "echo \"Question: \" . \$mcQuestion->question;\n";
echo "echo \"Options: \" . json_encode(\$mcQuestion->options);\n";
echo "echo \"Correct Answer: \" . \$mcQuestion->correct_answer;\n\n";

echo "// Check if user answered this question\n";
echo "\$userAnswer = App\\Models\\UserAnswer::where('question_id', \$mcQuestion->id)\n";
echo "    ->where('quiz_attempt_id', \$attempt->id)->first();\n";
echo "if (\$userAnswer) {\n";
echo "    echo \"User Answer: '\" . \$userAnswer->answer . \"'\";\n";
echo "    echo \"Is Correct: \" . (\$userAnswer->is_correct ? 'YES' : 'NO');\n";
echo "    echo \"Points Earned: \" . \$userAnswer->points_earned;\n";
echo "} else {\n";
echo "    echo \"No user answer found for this question\";\n";
echo "}\n\n";

echo "3. CHECK QUIZ RESULTS COMPONENT:\n";
echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";
echo "// Test the QuizResults component directly\n";
echo "\$quizResults = new App\\Livewire\\User\\QuizResults();\n";
echo "\$quizResults->mount(\$attempt->id);\n";
echo "\$debug = \$quizResults->debugUserAnswers();\n";
echo "print_r(\$debug);\n\n";

echo "4. DIRECT DATABASE CHECK:\n";
echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";
echo "// Raw database query to check data\n";
echo "\$rawAnswers = DB::table('user_answers')\n";
echo "    ->join('questions', 'user_answers.question_id', '=', 'questions.id')\n";
echo "    ->where('user_answers.quiz_attempt_id', \$attempt->id)\n";
echo "    ->where('questions.type', 'multiple_choice')\n";
echo "    ->select('user_answers.*', 'questions.question', 'questions.type', 'questions.options', 'questions.correct_answer')\n";
echo "    ->get();\n";
echo "foreach (\$rawAnswers as \$answer) {\n";
echo "    echo \"Question: \" . \$answer->question;\n";
echo "    echo \"User Answer: '\" . \$answer->answer . \"'\";\n";
echo "    echo \"Correct Answer: '\" . \$answer->correct_answer . \"'\";\n";
echo "    echo \"Is Correct: \" . (\$answer->is_correct ? 'YES' : 'NO');\n";
echo "    echo \"Options: \" . \$answer->options;\n";
echo "    echo \"---\";\n";
echo "}\n\n";

echo "5. CHECK FOR COMMON ISSUES:\n";
echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";

echo "POSSIBLE CAUSES:\n";
echo "a) Answer is being saved as empty string or null\n";
echo "b) Question ID mismatch between save and retrieve\n";
echo "c) User answer exists but answer field is empty\n";
echo "d) Collection key mismatch in QuizResults\n";
echo "e) Answer is saved but not being retrieved properly\n\n";

echo "MANUAL CHECK QUERIES:\n";
echo "// Check if answer field is literally empty\n";
echo "App\\Models\\UserAnswer::where('quiz_attempt_id', \$attempt->id)\n";
echo "    ->whereNull('answer')->count(); // Should be 0\n";
echo "App\\Models\\UserAnswer::where('quiz_attempt_id', \$attempt->id)\n";
echo "    ->where('answer', '')->count(); // Should be 0\n\n";

echo "// Check answer character encoding\n";
echo "foreach (\$userAnswers as \$answer) {\n";
echo "    echo \"Answer length: \" . strlen(\$answer->answer);\n";
echo "    echo \"Answer bytes: \" . bin2hex(\$answer->answer);\n";
echo "}\n\n";

echo "6. FIX ATTEMPTS:\n";
echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";
echo "If answers are being saved incorrectly:\n";
echo "// Update a specific answer for testing\n";
echo "\$testAnswer = App\\Models\\UserAnswer::where('quiz_attempt_id', \$attempt->id)->first();\n";
echo "\$testAnswer->answer = 'Test Answer';\n";
echo "\$testAnswer->save();\n";
echo "echo \"Updated answer to: \" . \$testAnswer->answer;\n\n";

echo "// Refresh the quiz results page and check if 'Test Answer' appears\n\n";

echo "7. ALTERNATIVE RETRIEVAL METHOD:\n";
echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";
echo "// Try alternative way to get user answers\n";
echo "\$alternativeAnswers = \$attempt->userAnswers()->get()->keyBy('question_id');\n";
echo "echo \"Alternative method count: \" . \$alternativeAnswers->count();\n";
echo "foreach (\$alternativeAnswers as \$qId => \$answer) {\n";
echo "    echo \"Q{$qId}: '\" . \$answer->answer . \"'\";\n";
echo "}\n\n";

echo "DEBUGGING STEPS:\n";
echo "1. Run the checks above in tinker\n";
echo "2. Look at the Laravel logs (storage/logs/laravel.log)\n";
echo "3. Check the debug output on the quiz results page\n";
echo "4. Verify data exists in database\n";
echo "5. Test with a fresh quiz attempt\n\n";

echo "If the issue persists, please share the output of steps 1-4 above.\n";
?>