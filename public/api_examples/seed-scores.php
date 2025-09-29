<?php
/**
 * Quiz Score Seeder for SQLite Database
 * 
 * This script automatically generates realistic-looking quiz score data
 * for testing or demonstration purposes in a SQLite database.
 * 
 * === HOW TO RUN THIS SCRIPT ===
 * 
 * From the command line:
 *   php /path/to/seed-scores.php [options]
 * 
 * Or from the script's directory:
 *   php seed-scores.php [options]
 * 
 * Options:
 *   --force    Overwrite existing scores (will delete previous scores)
 * 
 * Examples:
 *   php seed-scores.php              # Add scores for quizzes
 *   php seed-scores.php --force      # Clear and regenerate all scores
 * 
 * Requirements:
 *   - PHP 7.0+ with PDO SQLite extension
 *   - Write permissions to the database directory
 * 
 * Output:
 *   - Adds records to the SQLite database
 *   - Reports on the number of scores added
 */

// Include database helper
require_once(__DIR__ . '/db_helper.php');

// If the --force parameter was passed, this will overwrite existing scores
$forceOverwrite = in_array('--force', $argv);

// Set defaults for quiz parameters
$defaultParams = [
    'minScore' => 0.6, // 60% minimum score (as a fraction)
    'maxScore' => 1.0, // 100% maximum score (as a fraction)
    'attemptCount' => mt_rand(35, 120) // Random number of attempts between 35-120
];

// Initialize the database connection and tables
$pdo = getDbConnection();
if (!$pdo) {
    die("Failed to connect to the database. Make sure the PDO SQLite extension is enabled.\n");
}

// Initialize tables
if (!initDbTables($pdo)) {
    die("Failed to initialize database tables.\n");
}

// Clear existing scores if force option is used
if ($forceOverwrite) {
    try {
        $pdo->exec("DELETE FROM scores");
        echo "Cleared all existing scores from database.\n";
    } catch (PDOException $e) {
        echo "Error clearing scores: " . $e->getMessage() . "\n";
    }
}

// Function to recursively find quiz files
function findQuizFiles($dir = './../quizzes', &$results = []) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            findQuizFiles($path, $results);
        } else {
            // Only include .php files that likely contain quizzes
            if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                // Read the file and check if it contains quiz-related content
                $content = file_get_contents($path);
                if (strpos($content, 'quizData') !== false || 
                    strpos($content, 'quiz-container') !== false) {
                    
                    // Determine quiz name from path
                    $quizName = pathinfo($path, PATHINFO_FILENAME);
                    // For nested quizzes, include the subfolder
                    if (dirname($path) !== './../quizzes') {
                        $subDir = basename(dirname($path));
                        $quizName = $subDir . '_' . $quizName;
                    }
                    
                    $results[] = $quizName;
                }
            }
        }
    }
    
    return $results;
}

// Find all quiz files
$quizzes = findQuizFiles();
echo "Found " . count($quizzes) . " quizzes to seed.\n";

// Process each quiz
$processedCount = 0;
$totalScoresGenerated = 0;

// Function to generate a gaussian (normal) distributed random number
// Using Box-Muller transform
function stats_random_gaussian() {
    static $use_last = false;
    static $last;
    
    if ($use_last) {
        $use_last = false;
        return $last;
    }
    
    $u1 = mt_rand() / mt_getrandmax();
    $u2 = mt_rand() / mt_getrandmax();
    
    $z1 = sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);
    $z2 = sqrt(-2 * log($u1)) * sin(2 * M_PI * $u2);
    
    $use_last = true;
    $last = $z2;
    
    return $z1;
}

// Function to generate random user IDs
function randomUserId() {
    $prefixes = ['anon_', 'user_', 'guest_', 'visitor_'];
    $prefix = $prefixes[array_rand($prefixes)];
    return $prefix . substr(md5(uniqid(mt_rand(), true)), 0, 8);
}

foreach ($quizzes as $quizId) {
    echo "Seeding data for quiz: $quizId\n";
    
    // Parse topic from quiz ID if available
    $parts = preg_split('/-|_/', $quizId);
    $topic = 'general';
    
    if (count($parts) > 1) {
        // First part could be the topic
        $possibleTopic = strtolower($parts[0]);
        // Common topics list
        $commonTopics = ['react', 'angular', 'vue', 'javascript', 'js', 'python', 'java', 'c', 'cpp', 'php', 'ruby', 'go', 
                        'rust', 'esp32', 'arduino', 'raspberry', 'node', 'web', 'css', 'html', 'sql', 'database', 'cloud'];
        
        if (in_array($possibleTopic, $commonTopics)) {
            $topic = $possibleTopic;
        }
    }
    
    // Generate a title from the ID
    $title = ucwords(str_replace(['-', '_'], ' ', $quizId));
    
    // Set quiz parameters
    $totalQuestions = mt_rand(5, 15); // Random number of questions
    $attemptCount = $defaultParams['attemptCount'];
    
    // Start batch insert
    try {
        $pdo->beginTransaction();
        $scoresAdded = 0;
        
        // Get or create the quiz in the database
        $quizDbId = getOrCreateQuiz($pdo, $quizId, $topic, $title);
        
        // Generate scores using a more realistic distribution
        for ($i = 0; $i < $attemptCount; $i++) {
            // Use gaussian distribution for more realistic scores
            $gaussValue = stats_random_gaussian(); // Returns a value roughly between -3 and 3
            $normalizedGauss = ($gaussValue + 3) / 6; // Convert to 0-1 range
            
            // Apply the distribution to our score range, slightly skewed toward higher scores
            $scoreRatio = 0.5 + ($normalizedGauss * 0.5); // Convert to 0.5-1.0 range (weighted toward higher scores)
            $minScore = max(1, round($defaultParams['minScore'] * $totalQuestions));
            $maxScore = $totalQuestions;
            $scoreRange = $maxScore - $minScore;
            $score = $minScore + round($scoreRange * $scoreRatio);
            
            // Ensure score is within bounds
            $score = max($minScore, min($maxScore, $score));
            $percentage = round(($score / $totalQuestions) * 100);
            
            // Create timestamp with more recent scores being more frequent
            // This creates a pattern where quiz usage increases over time
            $daysAgo = floor(pow(mt_rand(0, 100) / 100, 2) * 180); // More recent dates are more likely
            $timestamp = strtotime("-$daysAgo days") + mt_rand(0, 86400);
            $date = date('Y-m-d H:i:s', $timestamp);
            
            // Generate user data
            $userName = 'User' . mt_rand(1000, 9999);
            $userEmail = strtolower($userName) . '@example.com';
            $userId = randomUserId();
            
            // Insert score directly into the database
            $stmt = $pdo->prepare("
                INSERT INTO scores 
                    (quiz_id, user_id, name, email, score, total_questions, percentage, timestamp, date) 
                VALUES 
                    (:quiz_id, :user_id, :name, :email, :score, :total_questions, :percentage, :timestamp, :date)
            ");
            
            $stmt->execute([
                ':quiz_id' => $quizDbId,
                ':user_id' => $userId,
                ':name' => $userName,
                ':email' => $userEmail,
                ':score' => $score,
                ':total_questions' => $totalQuestions,
                ':percentage' => $percentage,
                ':timestamp' => $timestamp,
                ':date' => $date
            ]);
            
            $scoresAdded++;
        }
        
        // Commit the transaction
        $pdo->commit();
        echo "  - Added $scoresAdded scores to database for quiz: $quizId\n";
        $totalScoresGenerated += $scoresAdded;
        $processedCount++;
        
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "  - Error adding scores for $quizId: " . $e->getMessage() . "\n";
    }
}

echo "\nSeeding complete! Generated $totalScoresGenerated scores for $processedCount quizzes.\n";
?>
