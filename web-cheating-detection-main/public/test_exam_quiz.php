<?php
$api = 'https://bgnuf22eight.com/Exam-app/exam-evaluation-app/public/api';

// Create a random student ID to ensure no previous attempts
$studentId = rand(1000, 9999);
$quizId = 179; // NRIO5R

echo "Testing with fake student ID: $studentId\n\n";

// 1. Call GET /api/exam-quiz to see if it secretly creates an attempt
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$api/exam-quiz/$quizId/$studentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$examRes = curl_exec($ch);
curl_close($ch);
echo "GET /exam-quiz/ Response:\n$examRes\n\n";

// 2. Call GET /api/quiz-attempt (actually Teacher See Quiz Attempts) to check if attempt exists
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$api/quiz-attempts/NRIO5R");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$attemptsRes = curl_exec($ch);
curl_close($ch);

$attemptsData = json_decode($attemptsRes, true);
$found = false;
if (isset($attemptsData['attempts'])) {
    foreach ($attemptsData['attempts'] as $att) {
        if ($att['student_id'] == $studentId && $att['status'] !== 'not_started') {
            echo "ATTEMPT WAS CREATED SECRETLY! Status: {$att['status']}\n";
            $found = true;
        }
    }
}
if (!$found) {
    echo "NO ATTEMPT WAS CREATED SECRETLY.\n";
}

