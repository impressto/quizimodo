<?php
// Allow cross-origin requests from your domain
header('Access-Control-Allow-Origin: *'); // Replace * with your domain in production
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the raw POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData);

// Validate the data
if (!$data || !isset($data->quizId) || !isset($data->score) || !isset($data->totalQuestions)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Basic sanitization and validation
$quizId = preg_replace('/[^a-zA-Z0-9\-_]/', '', $data->quizId);
$score = intval($data->score);
$totalQuestions = intval($data->totalQuestions);
$timestamp = time();

// Optional fields
$topic = isset($data->topic) ? preg_replace('/[^a-zA-Z0-9\-_]/', '', $data->topic) : '';
$quizTitle = isset($data->quizTitle) ? substr(strip_tags($data->quizTitle), 0, 100) : '';

// Anonymize user info
$userId = isset($data->anonymousId) ? $data->anonymousId : uniqid();

// Prepare score record
$scoreRecord = [
    'quizId' => $quizId,
    'topic' => $topic,
    'quizTitle' => $quizTitle,
    'score' => $score,
    'totalQuestions' => $totalQuestions,
    'percentage' => ($totalQuestions > 0) ? round(($score / $totalQuestions) * 100) : 0,
    'userId' => $userId,
    'timestamp' => $timestamp,
    'date' => date('Y-m-d H:i:s', $timestamp)
];

// Define the storage directory and file
$storageDir = __DIR__ . '/scores';
$scoresFile = $storageDir . '/' . $quizId . '_scores.json';

// Create directory if it doesn't exist
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

// Load existing scores
$scores = [];
if (file_exists($scoresFile)) {
    $fileContent = file_get_contents($scoresFile);
    if ($fileContent) {
        $scores = json_decode($fileContent, true) ?: [];
    }
}

// Append new score
$scores[] = $scoreRecord;

// Keep only the most recent scores to prevent file from growing too large
$maxScores = 1000; // Adjust as needed
if (count($scores) > $maxScores) {
    $scores = array_slice($scores, -$maxScores);
}

// Save scores back to file
if (file_put_contents($scoresFile, json_encode($scores, JSON_PRETTY_PRINT))) {
    http_response_code(200);
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save score']);
}