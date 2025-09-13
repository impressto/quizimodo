<?php
// Allow cross-origin requests from your domain
header('Access-Control-Allow-Origin: *'); // Replace * with your domain in production
header('Content-Type: application/json');

// Define the storage directory
$storageDir = __DIR__ . '/scores';

// Get quiz ID from query string
$quizId = isset($_GET['quizId']) ? preg_replace('/[^a-zA-Z0-9\-_]/', '', $_GET['quizId']) : null;

// If no quiz ID provided, return error
if (!$quizId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing quizId parameter']);
    exit;
}

// Define the scores file
$scoresFile = $storageDir . '/' . $quizId . '_scores.json';

// Check if the file exists
if (!file_exists($scoresFile)) {
    // Return empty stats if no scores available
    echo json_encode([
        'quizId' => $quizId,
        'totalAttempts' => 0,
        'averageScore' => 0,
        'highScore' => 0,
        'recentScores' => []
    ]);
    exit;
}

// Load scores
$fileContent = file_get_contents($scoresFile);
$scores = json_decode($fileContent, true) ?: [];

// Calculate statistics
$totalAttempts = count($scores);
$totalPercentage = 0;
$highScore = 0;

foreach ($scores as $score) {
    $totalPercentage += $score['percentage'];
    $highScore = max($highScore, $score['percentage']);
}

$averageScore = $totalAttempts > 0 ? round($totalPercentage / $totalAttempts) : 0;

// Get recent scores (last 10)
$recentScores = array_slice($scores, -10);
// Only include necessary fields for each score
$recentScoresFormatted = array_map(function($score) {
    return [
        'percentage' => $score['percentage'],
        'date' => $score['date']
    ];
}, $recentScores);

// Calculate score distribution
$distribution = [
    '0-20' => 0,
    '21-40' => 0,
    '41-60' => 0,
    '61-80' => 0,
    '81-100' => 0
];

foreach ($scores as $score) {
    $percentage = $score['percentage'];
    if ($percentage <= 20) $distribution['0-20']++;
    elseif ($percentage <= 40) $distribution['21-40']++;
    elseif ($percentage <= 60) $distribution['41-60']++;
    elseif ($percentage <= 80) $distribution['61-80']++;
    else $distribution['81-100']++;
}

// Return statistics
echo json_encode([
    'quizId' => $quizId,
    'totalAttempts' => $totalAttempts,
    'averageScore' => $averageScore,
    'highScore' => $highScore,
    'distribution' => $distribution,
    'recentScores' => $recentScoresFormatted
]);