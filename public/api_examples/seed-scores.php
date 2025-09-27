<?php
/**
 * Quiz Score Seeder
 * 
 * This script automatically scans for quiz files and generates realistic-looking 
 * quiz score data for testing or demonstration purposes.
 * It creates summary files and recent score data for all discovered quizzes.
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
 *   --force    Overwrite existing score files (backup copies will be created)
 * 
 * Examples:
 *   php seed-scores.php              # Generate scores for new quizzes only
 *   php seed-scores.php --force      # Generate scores for all quizzes, overwriting existing data
 * 
 * Requirements:
 *   - PHP 7.0+ installed and accessible from command line
 *   - Write permissions to the /scores directory
 * 
 * Output:
 *   - Creates/updates JSON files in the /scores directory
 *   - For each quiz, generates both summary statistics and recent scores
 *   - Backs up any existing files before overwriting them (when using --force)
 */

// Define paths
$storageDir = __DIR__ . '/scores';

// If the --force parameter was passed, this will overwrite existing score files
$forceOverwrite = in_array('--force', $argv);

// Set defaults for quiz parameters
$defaultParams = [
    'minScore' => 0.6, // 60% minimum score (as a fraction)
    'maxScore' => 1.0, // 100% maximum score (as a fraction)
    'attemptCount' => mt_rand(35, 120) // Random number of attempts between 35-120
];

// Scan scores directory for existing score files
echo "Scanning for existing score files in: $storageDir\n";

// Get all existing summary and recent files
$summaryFiles = glob("$storageDir/*_summary.json");
$recentFiles = glob("$storageDir/*_recent.json");
$backupFiles = glob("$storageDir/*.bak*");

// Get unique quiz IDs from existing files (both summary and old-style)
$quizIds = [];
$oldStyleFiles = glob("$storageDir/*_scores.json");

// Extract quiz IDs and topics from summary files
foreach ($summaryFiles as $file) {
    $filename = basename($file, '_summary.json');
    $parts = explode('_', $filename, 2);
    
    if (count($parts) > 1) {
        // New format: topic_quizId
        $topic = $parts[0];
        $quizId = $parts[1];
        $quizIds[$topic . '_' . $quizId] = ['quizId' => $quizId, 'topic' => $topic];
    } else {
        // Old format: just quizId
        $quizId = $filename;
        $quizIds[$quizId] = ['quizId' => $quizId, 'topic' => ''];
    }
}

// Extract quiz IDs and topics from recent files
foreach ($recentFiles as $file) {
    $filename = basename($file, '_recent.json');
    $parts = explode('_', $filename, 2);
    
    if (count($parts) > 1) {
        // New format: topic_quizId
        $topic = $parts[0];
        $quizId = $parts[1];
        $quizIds[$topic . '_' . $quizId] = ['quizId' => $quizId, 'topic' => $topic];
    } else {
        // Old format: just quizId
        $quizId = $filename;
        $quizIds[$quizId] = ['quizId' => $quizId, 'topic' => ''];
    }
}

// Extract quiz IDs from old-style files
foreach ($oldStyleFiles as $file) {
    $filename = basename($file, '_scores.json');
    $parts = explode('_', $filename, 2);
    
    if (count($parts) > 1) {
        // New format: topic_quizId
        $topic = $parts[0];
        $quizId = $parts[1];
        $quizIds[$topic . '_' . $quizId] = ['quizId' => $quizId, 'topic' => $topic];
    } else {
        // Old format: just quizId
        $quizId = $filename;
        $quizIds[$quizId] = ['quizId' => $quizId, 'topic' => ''];
    }
}

echo "Found " . count($quizIds) . " unique quiz IDs\n";

// Initialize quiz data array
$quizData = [];

// Process each unique quiz ID
foreach ($quizIds as $key => $quizInfo) {
    $quizId = $quizInfo['quizId'];
    $topic = $quizInfo['topic'];
    
    // Determine file paths based on topic
    $filePrefix = !empty($topic) ? $topic . '_' . $quizId : $quizId;
    $summaryFile = "$storageDir/{$filePrefix}_summary.json";
        // File paths using topic
    $oldStyleFile = $storageDir . '/' . $topic . '_' . $quiz['quizId'] . '_scores.json';
    $title = ucwords(str_replace(['-', '_'], ' ', $quizId));
    $totalQuestions = 10; // Default
    
    // Try to get more info from summary file if it exists
    if (file_exists($summaryFile)) {
        $summaryContent = file_get_contents($summaryFile);
        $summary = json_decode($summaryContent, true);
        if ($summary && isset($summary['quizTitle'])) {
            $title = $summary['quizTitle'];
        }
        if ($summary && isset($summary['topic'])) {
            $topic = $summary['topic'];
        }
    }
    // Try to get info from old-style file if it exists
    else if (file_exists($oldStyleFile)) {
        $oldContent = file_get_contents($oldStyleFile);
        $oldScores = json_decode($oldContent, true);
        if ($oldScores && isset($oldScores[0]['quizTitle'])) {
            $title = $oldScores[0]['quizTitle'];
        }
        if ($oldScores && isset($oldScores[0]['topic'])) {
            $topic = $oldScores[0]['topic'];
        }
        if ($oldScores && isset($oldScores[0]['totalQuestions'])) {
            $totalQuestions = $oldScores[0]['totalQuestions'];
        }
    }
    
    // Parse topic from quiz ID if not found elsewhere
    if (empty($topic)) {
        // Try to extract topic from quiz ID (common formats: topic-name or topic_name)
        $parts = preg_split('/-|_/', $quizId);
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
    }
    
    // Calculate min and max scores
    $minScore = max(1, round($defaultParams['minScore'] * $totalQuestions));
    $maxScore = $totalQuestions;
    
    // Make sure topic is set to default if empty
    if (empty($topic)) {
        $topic = 'default';
    }
    
    // Add to quiz data array
    $quizData[] = [
        'quizId' => $quizId,
        'topic' => $topic,
        'quizTitle' => $title,
        'totalQuestions' => $totalQuestions,
        'minScore' => $minScore,
        'maxScore' => $maxScore,
        'attemptCount' => $defaultParams['attemptCount']
    ];
    
    echo "Processing quiz: $title (ID: $quizId, Topic: $topic, Questions: $totalQuestions)\n";
}

// Define the storage directory
$storageDir = __DIR__ . '/scores';

// Create directory if it doesn't exist
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

// Function to generate a random date within a range
function randomDate($start, $end) {
    return date('Y-m-d H:i:s', mt_rand(strtotime($start), strtotime($end)));
}

// Function to generate random user IDs
function randomUserId() {
    $prefixes = ['anon_', 'user_', 'guest_', 'visitor_'];
    $prefix = $prefixes[array_rand($prefixes)];
    return $prefix . substr(md5(uniqid(mt_rand(), true)), 0, 8);
}

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

// Process each quiz
$processedCount = 0;

foreach ($quizData as $quiz) {
    // Get topic value or use default
    $topic = !empty($quiz['topic']) ? $quiz['topic'] : 'default';
    
    // File paths with topic included
    $summaryFile = $storageDir . '/' . $topic . '_' . $quiz['quizId'] . '_summary.json';
    $recentScoresFile = $storageDir . '/' . $topic . '_' . $quiz['quizId'] . '_recent.json';
    
    // Skip if files exist and not forcing overwrite
    if (!$forceOverwrite && file_exists($summaryFile) && file_exists($recentScoresFile)) {
        echo "Skipping quiz: {$quiz['quizTitle']} (ID: {$quiz['quizId']}) - files already exist\n";
        echo "  Use --force parameter to overwrite existing files\n\n";
        continue;
    }
    
    echo "Seeding data for quiz: {$quiz['quizTitle']} (ID: {$quiz['quizId']})\n";
    
    // Initialize stats
    $totalScore = 0;
    $highScore = 0;
    $distribution = [
        '0-20' => 0,
        '21-40' => 0,
        '41-60' => 0,
        '61-80' => 0,
        '81-100' => 0
    ];
    
    // Generate scores with a bell curve distribution (more scores in the middle ranges)
    $scores = [];
    $attemptCount = $quiz['attemptCount'];
    
    // Generate scores using a more realistic distribution
    for ($i = 0; $i < $attemptCount; $i++) {
        // Use gaussian distribution for more realistic scores
        $gaussValue = stats_random_gaussian(); // Returns a value roughly between -3 and 3
        $normalizedGauss = ($gaussValue + 3) / 6; // Convert to 0-1 range
        
        // Apply the distribution to our score range, slightly skewed toward higher scores
        $scoreRatio = 0.5 + ($normalizedGauss * 0.5); // Convert to 0.5-1.0 range (weighted toward higher scores)
        $scoreRange = $quiz['maxScore'] - $quiz['minScore'];
        $score = $quiz['minScore'] + round($scoreRange * $scoreRatio);
        
        // Ensure score is within bounds
        $score = max($quiz['minScore'], min($quiz['maxScore'], $score));
        $percentage = round(($score / $quiz['totalQuestions']) * 100);
        
        // Create timestamp with more recent scores being more frequent
        // This creates a pattern where quiz usage increases over time
        $daysAgo = floor(pow(mt_rand(0, 100) / 100, 2) * 180); // More recent dates are more likely
        $date = date('Y-m-d H:i:s', strtotime("-$daysAgo days") + mt_rand(0, 86400));
        
    // Create score record with more details for the combined file
    $scoreRecord = [
        'quizId' => $quiz['quizId'],
        'topic' => $quiz['topic'],
        'quizTitle' => $quiz['quizTitle'],
        'score' => $score,
        'totalQuestions' => $quiz['totalQuestions'],
        'percentage' => $percentage,
        'userId' => randomUserId(),
        'timestamp' => strtotime($date),
        'date' => $date
    ];
    
    // Add to scores array (with just percentage and date for recentScores)
    $scores[] = [
        'percentage' => $percentage,
        'date' => $date
    ];        // Update stats
        $totalScore += $percentage;
        $highScore = max($highScore, $percentage);
        
        // Update distribution
        if ($percentage <= 20) $distribution['0-20']++;
        elseif ($percentage <= 40) $distribution['21-40']++;
        elseif ($percentage <= 60) $distribution['41-60']++;
        elseif ($percentage <= 80) $distribution['61-80']++;
        else $distribution['81-100']++;
    }
    
    // Sort scores by date (oldest to newest)
    usort($scores, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });
    
    // Get recent scores (latest 20)
    $recentScores = array_slice($scores, -20);
    
    // Calculate average
    $averageScore = round($totalScore / $attemptCount);
    
    // Create summary
    $summary = [
        'quizId' => $quiz['quizId'],
        'topic' => $quiz['topic'],
        'quizTitle' => $quiz['quizTitle'],
        'totalAttempts' => $attemptCount,
        'totalScore' => $totalScore,
        'averageScore' => $averageScore,
        'highScore' => $highScore,
        'distribution' => $distribution,
        'lastUpdated' => end($scores)['date'],
        'generatedBy' => 'seed-scores.php',
        'generatedAt' => date('Y-m-d H:i:s')
    ];
    
    // Create the combined scores file name (using the new format with topic)
    $combinedScoresFile = $storageDir . '/' . $topic . '_' . $quiz['quizId'] . '_scores.json';
    
    // Create the full scoreRecords array for the combined file
    $fullScoreRecords = [];
    foreach ($scores as $index => $score) {
        // Create a full record with all details for each score
        $fullScoreRecords[] = [
            'quizId' => $quiz['quizId'],
            'topic' => $quiz['topic'],
            'quizTitle' => $quiz['quizTitle'],
            'score' => round($quiz['minScore'] + ($index % ($quiz['maxScore'] - $quiz['minScore'] + 1))),
            'totalQuestions' => $quiz['totalQuestions'],
            'percentage' => $score['percentage'],
            'userId' => randomUserId(),
            'timestamp' => strtotime($score['date']),
            'date' => $score['date']
        ];
    }
    
    // Check if files already exist and back them up
    foreach ([$summaryFile, $recentScoresFile, $combinedScoresFile] as $file) {
        if (file_exists($file)) {
            $backupFile = $file . '.bak.' . date('YmdHis');
            if (!rename($file, $backupFile)) {
                echo "  - Warning: Could not back up existing file: " . basename($file) . "\n";
            } else {
                echo "  - Backed up existing file: " . basename($file) . " → " . basename($backupFile) . "\n";
            }
        }
    }
    
    // Save files
    if (file_put_contents($summaryFile, json_encode($summary, JSON_PRETTY_PRINT))) {
        echo "  - Created summary file: " . basename($summaryFile) . "\n";
        echo "    (Topic: {$quiz['topic']}, Quiz ID: {$quiz['quizId']})\n";
    } else {
        echo "  - Error: Could not create summary file\n";
    }
    
    if (file_put_contents($recentScoresFile, json_encode($recentScores, JSON_PRETTY_PRINT))) {
        echo "  - Created recent scores file: " . basename($recentScoresFile) . "\n";
    } else {
        echo "  - Error: Could not create recent scores file\n";
    }
    
    // Save the combined scores file (compatible with app.py format)
    if (file_put_contents($combinedScoresFile, json_encode($fullScoreRecords, JSON_PRETTY_PRINT))) {
        echo "  - Created combined scores file: " . basename($combinedScoresFile) . "\n";
    } else {
        echo "  - Error: Could not create combined scores file\n";
    }
    
    echo "  - Done!\n\n";
    $processedCount++;
}

echo "\nSeeding complete! Generated data for $processedCount quizzes.\n";
?>
