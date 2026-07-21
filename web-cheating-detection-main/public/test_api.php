<?php
// Find real quiz IDs with results - scan higher quiz IDs
$api = 'https://bgnuf22eight.com/Exam-app/exam-evaluation-app/public/api';

// Student IDs to try
$studentIds = [1, 2, 3, 4, 5, 6, 7, 8];
// Quiz IDs - try a wider range
$quizIds = range(1, 50);

foreach ($quizIds as $quizId) {
    foreach ($studentIds as $studentId) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$api/quiz/result/$quizId/$studentId");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $res = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($res, true);
        if (!empty($data['status'])) {
            echo "=== FOUND: Quiz $quizId / Student $studentId ===\n";
            echo "Keys: " . implode(', ', array_keys($data)) . "\n";
            if (isset($data['short_answers'])) {
                echo "short_answers: " . count($data['short_answers']) . " items\n";
                if (!empty($data['short_answers'])) {
                    echo "First SA keys: " . implode(', ', array_keys($data['short_answers'][0])) . "\n";
                    echo json_encode($data['short_answers'][0], JSON_PRETTY_PRINT) . "\n";
                }
            } else {
                echo "No short_answers key. Full response:\n";
                echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
            }
            echo "\n";
        }
    }
}
echo "SCAN DONE\n";
