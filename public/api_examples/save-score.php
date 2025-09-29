<?php
// Include database helper
require_once('db_helper.php');

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

// Check payload size (limit to 10KB to prevent data bombing)
$maxPayloadSize = 10 * 1024; // 10KB
if (strlen($rawData) > $maxPayloadSize) {
    http_response_code(413); // Payload Too Large
    echo json_encode(['error' => 'Payload too large. Maximum allowed size is ' . $maxPayloadSize . ' bytes.']);
    exit;
}

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
    'topic' => !empty($topic) ? $topic : 'default',
    'quizTitle' => $quizTitle,
    'score' => $score,
    'totalQuestions' => $totalQuestions,
    'userId' => $userId,
    'timestamp' => $timestamp,
    'date' => date('Y-m-d H:i:s', $timestamp)
];

// Save the score to the database
if (saveScore($scoreRecord)) {
    http_response_code(200);
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save score to database']);
}