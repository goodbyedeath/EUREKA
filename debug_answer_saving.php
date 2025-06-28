<?php
/**
 * Debug Answer Saving Issue - Step by Step Guide
 * 
 * Issue: Multiple choice answers show "No answer provided" even when selected
 * Diagnosis: UserAnswer=null means no database record exists
 * Root Cause: Answer saving process is failing somewhere
 */

echo "=== MULTIPLE CHOICE ANSWER SAVING DEBUG GUIDE ===\n\n";

echo "🔍 ISSUE DIAGNOSIS:\n";
echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";
echo "• Problem: Quiz results show 'No answer provided (Debug: UserAnswer=null, Answer=null)'\n";
echo "• Root Cause: No UserAnswer records are being saved to the database\n";
echo "• Impact: All multiple choice selections are lost after quiz submission\n\n";

echo "📊 ANSWER SAVING FLOW ANALYSIS:\n";
echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";
echo "1. User selects multiple choice option → wire:model.live triggers\n";
echo "2. Livewire calls updatedAnswers(\$value, \$key) method\n";
echo "3. updatedAnswers() calls saveAnswer(\$questionId, \$answer)\n";
echo "4. saveAnswer() adds answer to \$pendingAnswers array\n";
echo "5. Answers are batched until: 5+ answers, navigation, or quiz submission\n";
echo "6. flushPendingAnswers() saves all pending answers to database\n";
echo "7. Quiz submission calls flushPendingAnswers() before completion\n\n";

echo "🚨 POTENTIAL FAILURE POINTS:\n";
echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";
echo "A. wire:model.live not triggering (frontend issue)\n";
echo "B. updatedAnswers() not being called (Livewire issue)\n";
echo "C. saveAnswer() validation failing\n";
echo "D. Answers not being added to pendingAnswers\n";
echo "E. flushPendingAnswers() not being called during submission\n";
echo "F. Database transaction failing\n";
echo "G. Quiz attempt canEditAnswers() returning false\n\n";

echo "🔧 DEBUGGING STEPS:\n";
echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";
echo "STEP 1: Enable Debug Mode\n";
echo "• Set APP_DEBUG=true in .env file\n";
echo "• Clear config cache: php artisan config:clear\n\n";

echo "STEP 2: Take a Fresh Quiz\n";
echo "• Start a new quiz with multiple choice questions\n";
echo "• Select answers for each question\n";
echo "• Submit the quiz\n";
echo "• Go to results page\n\n";

echo "STEP 3: Check Laravel Logs\n";
echo "• Monitor: tail -f storage/logs/laravel.log\n";
echo "• Look for these debug messages:\n";
echo "  - 'QuizTake: updatedAnswers triggered'\n";
echo "  - 'QuizTake: saveAnswer called'\n";
echo "  - 'QuizTake: Answer validated successfully'\n";
echo "  - 'QuizTake: Answer added to pending'\n";
echo "  - 'QuizTake: About to flush pending answers before submission'\n";
echo "  - 'QuizTake: flushPendingAnswers called'\n";
echo "  - 'QuizTake: Saving X answers to database'\n";
echo "  - 'QuizTake: Successfully saved answers to database'\n\n";

echo "STEP 4: Frontend Debug (Browser Console)\n";
echo "• Open browser developer tools\n";
echo "• Go to Console tab\n";
echo "• Look for JavaScript errors\n";
echo "• Check Network tab for Livewire requests\n\n";

echo "STEP 5: Database Direct Check\n";
echo "• Run: php artisan tinker\n";
echo "• Check latest attempt: \$attempt = App\\Models\\QuizAttempt::latest()->first();\n";
echo "• Check user answers: App\\Models\\UserAnswer::where('quiz_attempt_id', \$attempt->id)->get();\n";
echo "• Verify empty result confirms the issue\n\n";

echo "🛠️ IMMEDIATE FIXES TO TRY:\n";
echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";

echo "FIX 1: Force Immediate Saving (Temporary)\n";
echo "Edit QuizTake.php saveAnswer() method, change:\n";
echo "if (count(\$this->pendingAnswers) >= 5) {\n";
echo "to:\n";
echo "if (count(\$this->pendingAnswers) >= 1) { // Save immediately\n\n";

echo "FIX 2: Add Manual Flush on Each Answer\n";
echo "In updatedAnswers() method, after saveAnswer() call, add:\n";
echo "\$this->flushPendingAnswers(); // Force immediate save\n\n";

echo "FIX 3: Check Quiz Attempt Status\n";
echo "Add debugging to canEditAnswers() method to see if it's blocking saves\n\n";

echo "📋 LOG ANALYSIS GUIDE:\n";
echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";

echo "IF YOU SEE: 'updatedAnswers triggered'\n";
echo "→ Frontend is working, Livewire is receiving changes\n\n";

echo "IF YOU DON'T SEE: 'updatedAnswers triggered'\n";
echo "→ Frontend issue: wire:model.live not working\n";
echo "→ Check for JavaScript errors\n";
echo "→ Verify Livewire is loaded properly\n\n";

echo "IF YOU SEE: 'updatedAnswers' but NOT 'saveAnswer called'\n";
echo "→ updatedAnswers() logic is failing\n";
echo "→ Check key format (should be numeric question ID)\n";
echo "→ Check canEditAnswers() status\n\n";

echo "IF YOU SEE: 'saveAnswer called' but NOT 'Answer validated successfully'\n";
echo "→ Answer validation is failing\n";
echo "→ Check AnswerValidationService\n";
echo "→ Verify question options and correct_answer format\n\n";

echo "IF YOU SEE: 'Answer added to pending' but NOT 'Saving X answers to database'\n";
echo "→ flushPendingAnswers() is not being called\n";
echo "→ Quiz submission might be failing\n";
echo "→ Check quiz completion flow\n\n";

echo "IF YOU SEE: 'Saving X answers to database' but NOT 'Successfully saved'\n";
echo "→ Database transaction is failing\n";
echo "→ Check database connectivity\n";
echo "→ Check for database errors in logs\n\n";

echo "🎯 EXPECTED SUCCESSFUL LOG SEQUENCE:\n";
echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";
echo "For each answer selection:\n";
echo "1. QuizTake: updatedAnswers triggered - Key: X, Value: 'answer'\n";
echo "2. QuizTake: Calling saveAnswer from updatedAnswers - Key: X, Value: 'answer'\n";
echo "3. QuizTake: saveAnswer called - QuestionID: X, Answer: 'answer'\n";
echo "4. QuizTake: Answer validated successfully - QuestionID: X, ValidatedAnswer: 'answer'\n";
echo "5. QuizTake: Answer added to pending - QuestionID: X, PendingCount: Y\n\n";

echo "During quiz submission:\n";
echo "6. QuizTake: About to flush pending answers before submission. Pending count: Y\n";
echo "7. QuizTake: flushPendingAnswers called - PendingCount: Y\n";
echo "8. QuizTake: Saving Y answers to database\n";
echo "9. QuizTake: Saving - QX: 'answer' (Correct: YES/NO)\n";
echo "10. QuizTake: Successfully saved answers to database\n\n";

echo "💡 QUICK TEST COMMANDS:\n";
echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";
echo "# Enable debug mode\n";
echo "sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env\n";
echo "php artisan config:clear\n\n";

echo "# Monitor logs in real time\n";
echo "tail -f storage/logs/laravel.log | grep 'QuizTake:'\n\n";

echo "# Check for any quiz-related errors\n";
echo "tail -n 100 storage/logs/laravel.log | grep -i error\n\n";

echo "# Quick database check\n";
echo "php artisan tinker --execute=\"\n";
echo "\\$attempt = App\\Models\\QuizAttempt::latest()->first();\n";
echo "echo 'Attempt: ' . \\$attempt->id;\n";
echo "echo 'Status: ' . \\$attempt->status;\n";
echo "echo 'Answers: ' . App\\Models\\UserAnswer::where('quiz_attempt_id', \\$attempt->id)->count();\n";
echo "\"\n\n";

echo "🚀 ACTION PLAN:\n";
echo "1. Enable debug mode\n";
echo "2. Take a quiz with debug monitoring\n";
echo "3. Check logs for the exact failure point\n";
echo "4. Apply appropriate fix based on log analysis\n";
echo "5. Test again to confirm fix\n\n";

echo "Please run through these steps and share the log output!\n";
?>