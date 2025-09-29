<?php
/**
 * Test script for SQLite database implementation
 * 
 * This script tests the SQLite database functions for storing quiz scores.
 */

require_once 'db_helper.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>SQLite Quiz Database Test</h1>";

// Test database connection
echo "<h2>Testing Database Connection</h2>";
$pdo = getDbConnection();
if ($pdo) {
    echo "<p>✅ Database connection successful</p>";
} else {
    echo "<p>❌ Database connection failed</p>";
    exit;
}

// Test initialization of tables
echo "<h2>Testing Table Initialization</h2>";
$result = initDbTables($pdo);
if ($result) {
    echo "<p>✅ Database tables initialized successfully</p>";
} else {
    echo "<p>❌ Failed to initialize database tables</p>";
    exit;
}

// Test seed function
echo "<h2>Testing Seed Function</h2>";
$quizId = "test-quiz-" . time();
$result = seedQuizScores($quizId, "test", "Test Quiz", 20, 0.5, 1.0);
if ($result) {
    echo "<p>✅ Successfully seeded 20 quiz scores</p>";
} else {
    echo "<p>❌ Failed to seed quiz scores</p>";
}

// Test save score function
echo "<h2>Testing Save Score Function</h2>";
$scoreData = [
    'quizId' => $quizId,
    'topic' => 'test',
    'quizTitle' => 'Test Quiz',
    'userId' => 'test_user_' . time(),
    'name' => 'Test User',
    'email' => 'test@example.com',
    'score' => 8,
    'totalQuestions' => 10,
    'timestamp' => time()
];

$result = saveScore($scoreData);
if ($result) {
    echo "<p>✅ Successfully saved a quiz score</p>";
} else {
    echo "<p>❌ Failed to save quiz score</p>";
}

// Test get quiz stats function
echo "<h2>Testing Get Quiz Stats Function</h2>";
$stats = getQuizStats($quizId, 'test');
if ($stats) {
    echo "<p>✅ Successfully retrieved quiz statistics</p>";
    echo "<p>Total attempts: {$stats['totalAttempts']}</p>";
    echo "<p>Average score: {$stats['averageScore']}%</p>";
    echo "<p>High score: {$stats['highScore']}%</p>";
    
    echo "<h3>Score Distribution</h3>";
    echo "<ul>";
    foreach ($stats['distribution'] as $range => $count) {
        echo "<li>{$range}%: {$count} attempts</li>";
    }
    echo "</ul>";
    
    echo "<h3>Recent Scores</h3>";
    echo "<ul>";
    foreach ($stats['recentScores'] as $score) {
        echo "<li>{$score['date']}: {$score['percentage']}% ({$score['correct']}/{$score['total']})" .
             ($score['name'] ? " - {$score['name']}" : "") . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ Failed to retrieve quiz statistics</p>";
}

// Test list quizzes function
echo "<h2>Testing List Quizzes Function</h2>";
$quizzes = listQuizzes();
if ($quizzes) {
    echo "<p>✅ Successfully listed quizzes</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Quiz ID</th><th>Topic</th><th>Title</th><th>Attempts</th><th>Avg Score</th><th>High Score</th><th>Last Attempt</th></tr>";
    foreach ($quizzes as $quiz) {
        echo "<tr>";
        echo "<td>{$quiz['quizId']}</td>";
        echo "<td>{$quiz['topic']}</td>";
        echo "<td>{$quiz['title']}</td>";
        echo "<td>{$quiz['attemptCount']}</td>";
        echo "<td>{$quiz['averageScore']}%</td>";
        echo "<td>{$quiz['highScore']}%</td>";
        echo "<td>{$quiz['lastAttempt']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Failed to list quizzes</p>";
}

echo "<p>SQLite tests completed!</p>";
