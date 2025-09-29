<?php
// Include database helper
require_once('db_helper.php');

// Allow cross-origin requests from your domain
header('Access-Control-Allow-Origin: *'); // Replace * with your domain in production
header('Content-Type: application/json');

// Get quiz ID and topic from query string
$quizId = isset($_GET['quizId']) ? preg_replace('/[^a-zA-Z0-9\-_]/', '', $_GET['quizId']) : null;
$topic = isset($_GET['topic']) ? preg_replace('/[^a-zA-Z0-9\-_]/', '', $_GET['topic']) : 'default';

// If no quiz ID provided, return error
if (!$quizId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing quizId parameter']);
    exit;
}

// Get quiz statistics from the database
$stats = getQuizStats($quizId, $topic);

if ($stats === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve quiz statistics']);
    exit;
}

// Return the statistics as JSON
echo json_encode($stats);