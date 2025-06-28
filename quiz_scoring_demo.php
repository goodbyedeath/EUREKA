<?php
/**
 * Quiz Point-Based Scoring System Demo
 * 
 * This script demonstrates how points are calculated for different question types
 */

echo "=== EUREKA Quiz Point-Based Scoring System ===\n\n";

// Demo scoring scenarios
$scoringScenarios = [
    [
        'question_type' => 'multiple_choice',
        'question' => 'What is the capital of France?',
        'options' => ['London', 'Paris', 'Berlin', 'Madrid'],
        'correct_answer' => 'Paris',
        'points' => 5,
        'user_answers' => [
            ['answer' => 'Paris', 'expected_points' => 5],
            ['answer' => 'London', 'expected_points' => 0],
        ]
    ],
    [
        'question_type' => 'true_false',
        'question' => 'The Earth is round.',
        'correct_answer' => 'true',
        'points' => 3,
        'user_answers' => [
            ['answer' => 'true', 'expected_points' => 3],
            ['answer' => 'false', 'expected_points' => 0],
        ]
    ],
    [
        'question_type' => 'text',
        'question' => 'Name a programming language.',
        'correct_answer' => 'PHP, JavaScript, Python, Java', // Multiple acceptable answers
        'points' => 4,
        'user_answers' => [
            ['answer' => 'PHP', 'expected_points' => 4],
            ['answer' => 'javascript', 'expected_points' => 4], // Case insensitive
            ['answer' => 'C++', 'expected_points' => 0], // Not in acceptable list
        ]
    ],
    [
        'question_type' => 'fun_game',
        'question' => 'Complete the treasure hunt game',
        'points' => 0, // Points calculated via assessment
        'assessment_scenarios' => [
            [
                'team_base_points' => 1000,
                'additional_points' => 10, // Bonus for good performance
                'penalty' => 0,
                'total_assessment_points' => 1010
            ],
            [
                'team_base_points' => 1000,
                'additional_points' => 5,
                'penalty' => 100, // Penalty for rule violations
                'total_assessment_points' => 905
            ]
        ]
    ]
];

foreach ($scoringScenarios as $scenario) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "QUESTION TYPE: " . strtoupper(str_replace('_', ' ', $scenario['question_type'])) . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Question: {$scenario['question']}\n";
    
    if (isset($scenario['options'])) {
        echo "Options: " . implode(', ', $scenario['options']) . "\n";
        echo "Correct Answer: {$scenario['correct_answer']}\n";
    } elseif (isset($scenario['correct_answer'])) {
        echo "Correct Answer: {$scenario['correct_answer']}\n";
    }
    
    echo "Base Points: {$scenario['points']}\n\n";
    
    if ($scenario['question_type'] === 'fun_game') {
        echo "📊 ASSESSMENT-BASED SCORING:\n";
        echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";
        foreach ($scenario['assessment_scenarios'] as $i => $assessment) {
            echo "Scenario " . ($i + 1) . ":\n";
            echo "  • Team Base Points: {$assessment['team_base_points']}\n";
            echo "  • Additional Points: +{$assessment['additional_points']}\n";
            echo "  • Penalty: -{$assessment['penalty']}\n";
            echo "  • Total Assessment Points: {$assessment['total_assessment_points']}\n\n";
        }
    } else {
        echo "📊 AUTOMATIC SCORING RESULTS:\n";
        echo "───────────────────────────────────────────────────────────────────────────────────────────────────\n";
        foreach ($scenario['user_answers'] as $userAnswer) {
            $isCorrect = ($userAnswer['expected_points'] > 0) ? '✅ CORRECT' : '❌ INCORRECT';
            echo "User Answer: '{$userAnswer['answer']}' → {$isCorrect} → {$userAnswer['expected_points']} points\n";
        }
        echo "\n";
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SCORING SYSTEM SUMMARY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "🎯 REGULAR QUESTIONS (Multiple Choice, True/False, Text):\n";
echo "   • Points are defined per question (configurable)\n";
echo "   • Binary scoring: Full points if correct, 0 if incorrect\n";
echo "   • Calculated automatically using AnswerValidationService\n";
echo "   • Supports case-insensitive matching for text questions\n";
echo "   • Supports multiple acceptable answers for text questions\n\n";

echo "🎮 FUN GAME QUESTIONS:\n";
echo "   • Use assessment-based scoring system\n";
echo "   • Formula: Total = Team Base Points + Additional Points - Penalties\n";
echo "   • Manual evaluation through GameAssessmentForm\n";
echo "   • Can result in negative scores if penalties exceed base points\n";
echo "   • Points are added to team's total points\n\n";

echo "📈 FINAL QUIZ SCORE CALCULATION:\n";
echo "   • Regular Questions Score: Sum of earned points from all non-fun-game questions\n";
echo "   • Assessment Score: Sum of total_deposit from all completed assessments\n";
echo "   • Overall Percentage: (Total Earned Points / Total Possible Points) × 100\n";
echo "   • Both scoring systems are point-based and combined in final results\n\n";

echo "✅ ENHANCED QUIZ RESULTS NOW DISPLAY:\n";
echo "   • Question type badges (Multiple Choice, True False, Text, Fun Game)\n";
echo "   • Detailed answer breakdowns for each question type\n";
echo "   • Point calculation explanations\n";
echo "   • Visual indicators for correct/incorrect answers\n";
echo "   • Comprehensive scoring breakdowns\n";
echo "   • Assessment details with point components\n\n";

echo "🚀 The scoring system is fully point-based and provides detailed feedback!\n";
?>