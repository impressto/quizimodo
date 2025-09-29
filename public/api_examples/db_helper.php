<?php
/**
 * Quiz Database Helper Class
 * 
 * This class provides database connection and helper functions for the quiz system.
 * It uses SQLite to store quiz scores and statistics.
 */

class DBHelper {
    private $pdo = null;
    private $dbFile;
    private $dbDir;
    private $scoresDir;
    private $useSQLite = false;
    private $inTransaction = false;
    private $transactionScores = [];
    
    public function __construct() {
        $this->dbFile = __DIR__ . '/scores/quiz_scores.db';
        $this->dbDir = dirname($this->dbFile);
        $this->scoresDir = __DIR__ . '/scores';
        
        // Ensure the directory exists
        if (!is_dir($this->dbDir)) {
            if (!mkdir($this->dbDir, 0755, true)) {
                error_log("Failed to create database directory: {$this->dbDir}");
            }
        }
        
        // Check if SQLite driver is available
        $this->useSQLite = extension_loaded('pdo_sqlite') && in_array('sqlite', PDO::getAvailableDrivers());
    }
    
    /**
     * Connect to the SQLite database or initialize file storage
     * 
     * @return bool Success or failure
     */
    public function connect() {
        try {
            // Check if we're using SQLite
            if ($this->useSQLite) {
                // Create/connect to the database
                $this->pdo = new PDO("sqlite:{$this->dbFile}");
                
                // Set error mode to exceptions
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Set proper permissions for the database file
                if (file_exists($this->dbFile)) {
                    chmod($this->dbFile, 0666);
                }
            } else {
                // Make sure scores directory exists for file-based storage
                if (!is_dir($this->scoresDir)) {
                    if (!mkdir($this->scoresDir, 0755, true)) {
                        error_log("Failed to create scores directory: {$this->scoresDir}");
                        return false;
                    }
                }
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Initialize database tables if they don't exist
     * 
     * @return bool Success or failure
     */
    public function initTables() {
        try {
            if (!$this->connect()) {
                return false;
            }
            
            // If using SQLite, create tables
            if ($this->useSQLite && $this->pdo) {
                // Create quizzes table
                $this->pdo->exec("CREATE TABLE IF NOT EXISTS quizzes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    quiz_id TEXT NOT NULL,
                    topic TEXT NOT NULL,
                    title TEXT,
                    UNIQUE(quiz_id, topic)
                )");
                
                // Create scores table
                $this->pdo->exec("CREATE TABLE IF NOT EXISTS scores (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    quiz_id INTEGER NOT NULL,
                    user_id TEXT NOT NULL,
                    name TEXT,
                    email TEXT,
                    score REAL NOT NULL,
                    correct INTEGER NOT NULL,
                    total INTEGER NOT NULL,
                    timestamp INTEGER NOT NULL,
                    date TEXT NOT NULL,
                    FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
                )");
                
                // Create index on quiz_id for faster lookups
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_scores_quiz_id ON scores(quiz_id)");
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_scores_timestamp ON scores(timestamp)");
            }
            // For file-based storage, nothing to do as files are created on demand
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Failed to initialize tables: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Start a database transaction
     */
    public function beginTransaction() {
        if ($this->useSQLite && $this->pdo) {
            $this->pdo->beginTransaction();
        } else {
            // For file storage, we'll buffer scores until commit
            $this->inTransaction = true;
            $this->transactionScores = [];
        }
    }
    
    /**
     * Commit a database transaction
     */
    public function commitTransaction() {
        if ($this->useSQLite && $this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->commit();
        } else if ($this->inTransaction) {
            // For file storage, write all buffered scores
            foreach ($this->transactionScores as $score) {
                $this->saveScoreToFile($score);
            }
            $this->inTransaction = false;
            $this->transactionScores = [];
        }
    }
    
    /**
     * Roll back a database transaction
     */
    public function rollbackTransaction() {
        if ($this->useSQLite && $this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        } else if ($this->inTransaction) {
            // For file storage, just discard the buffered scores
            $this->inTransaction = false;
            $this->transactionScores = [];
        }
    }
    
    /**
     * Clear all scores from the database
     * 
     * @return bool Success or failure
     */
    public function clearAllScores() {
        try {
            if (!$this->connect()) {
                return false;
            }
            
            if ($this->useSQLite && $this->pdo) {
                $this->pdo->exec("DELETE FROM scores");
            } else {
                // For file storage, delete all score files
                $files = glob("{$this->scoresDir}/*_scores.json");
                $summaryFiles = glob("{$this->scoresDir}/*_summary.json");
                $recentFiles = glob("{$this->scoresDir}/*_recent.json");
                
                foreach (array_merge($files, $summaryFiles, $recentFiles) as $file) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to clear scores: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get or create a quiz record
     * 
     * @param string $quizId Quiz identifier
     * @param string $topic Quiz topic
     * @param string $title Quiz title (optional)
     * @return int|string The quiz record ID or the quiz ID string for file storage
     */
    private function getOrCreateQuiz($quizId, $topic, $title = null) {
        if ($this->useSQLite && $this->pdo) {
            // First try to find the quiz
            $stmt = $this->pdo->prepare("SELECT id FROM quizzes WHERE quiz_id = :quiz_id AND topic = :topic");
            $stmt->execute([
                ':quiz_id' => $quizId,
                ':topic' => $topic
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If quiz exists, return its ID
            if ($result) {
                return $result['id'];
            }
            
            // Otherwise, create a new quiz record
            $stmt = $this->pdo->prepare("INSERT INTO quizzes (quiz_id, topic, title) VALUES (:quiz_id, :topic, :title)");
            $stmt->execute([
                ':quiz_id' => $quizId,
                ':topic' => $topic,
                ':title' => $title
            ]);
            
            return $this->pdo->lastInsertId();
        } else {
            // For file storage, we just need the quiz ID and topic
            return $topic . '_' . $quizId;
        }
    }
    
    /**
     * Save a score to a file (for file-based storage)
     * 
     * @param array $scoreData The score data
     * @return bool Success or failure
     */
    private function saveScoreToFile($scoreData) {
        try {
            // Make sure we have the topic
            $topic = $scoreData['topic'] ?? 'default';
            $quizId = $scoreData['quiz'];
            
            // Calculate percentage if not provided
            $percentage = isset($scoreData['percentage']) ? $scoreData['percentage'] : 
                         (($scoreData['total'] > 0) ? round(($scoreData['correct'] / $scoreData['total']) * 100) : 0);
            
            // Prepare timestamp and date
            $timestamp = $scoreData['timestamp'] ?? time();
            $date = isset($scoreData['date']) ? $scoreData['date'] : 
                   (isset($scoreData['timestamp']) ? date('Y-m-d H:i:s', $scoreData['timestamp']) : date('Y-m-d H:i:s'));
            
            // Create file paths
            $filePrefix = $topic . '_' . $quizId;
            $summaryFile = "{$this->scoresDir}/{$filePrefix}_summary.json";
            $recentScoresFile = "{$this->scoresDir}/{$filePrefix}_recent.json";
            $combinedScoresFile = "{$this->scoresDir}/{$filePrefix}_scores.json";
            
            // Load existing data or create new
            $summary = [];
            $recentScores = [];
            $allScores = [];
            
            if (file_exists($summaryFile)) {
                $summary = json_decode(file_get_contents($summaryFile), true) ?: [];
            }
            
            if (file_exists($recentScoresFile)) {
                $recentScores = json_decode(file_get_contents($recentScoresFile), true) ?: [];
            }
            
            if (file_exists($combinedScoresFile)) {
                $allScores = json_decode(file_get_contents($combinedScoresFile), true) ?: [];
            }
            
            // Create new score record
            $scoreRecord = [
                'percentage' => $percentage,
                'date' => $date,
                'name' => $scoreData['name'] ?? null,
                'correct' => $scoreData['correct'],
                'total' => $scoreData['total']
            ];
            
            // Create full score record
            $fullScoreRecord = [
                'quizId' => $quizId,
                'topic' => $topic,
                'quizTitle' => ucwords(str_replace(['-', '_'], ' ', $quizId)),
                'score' => $scoreData['correct'],
                'totalQuestions' => $scoreData['total'],
                'percentage' => $percentage,
                'userId' => $scoreData['userId'] ?? uniqid('user_'),
                'timestamp' => $timestamp,
                'date' => $date,
                'name' => $scoreData['name'] ?? null,
                'email' => $scoreData['email'] ?? null
            ];
            
            // Add to recent scores and keep only latest 20
            array_unshift($recentScores, $scoreRecord);
            $recentScores = array_slice($recentScores, 0, 20);
            
            // Add to all scores
            $allScores[] = $fullScoreRecord;
            
            // Calculate distribution
            $distribution = [
                '0-20' => 0,
                '21-40' => 0,
                '41-60' => 0,
                '61-80' => 0,
                '81-100' => 0
            ];
            
            $totalScore = 0;
            $highScore = 0;
            
            foreach ($allScores as $score) {
                $totalScore += $score['percentage'];
                $highScore = max($highScore, $score['percentage']);
                
                if ($score['percentage'] <= 20) $distribution['0-20']++;
                elseif ($score['percentage'] <= 40) $distribution['21-40']++;
                elseif ($score['percentage'] <= 60) $distribution['41-60']++;
                elseif ($score['percentage'] <= 80) $distribution['61-80']++;
                else $distribution['81-100']++;
            }
            
            // Update summary
            $summary = [
                'quizId' => $quizId,
                'topic' => $topic,
                'quizTitle' => ucwords(str_replace(['-', '_'], ' ', $quizId)),
                'totalAttempts' => count($allScores),
                'averageScore' => count($allScores) > 0 ? round($totalScore / count($allScores)) : 0,
                'highScore' => $highScore,
                'distribution' => $distribution,
                'lastUpdated' => $date,
                'generatedAt' => date('Y-m-d H:i:s')
            ];
            
            // Save files
            file_put_contents($summaryFile, json_encode($summary, JSON_PRETTY_PRINT));
            file_put_contents($recentScoresFile, json_encode($recentScores, JSON_PRETTY_PRINT));
            file_put_contents($combinedScoresFile, json_encode($allScores, JSON_PRETTY_PRINT));
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error saving score to file: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save a quiz score
     * 
     * @param array $scoreData Score data with keys: quiz, topic, name, email, userId, score, correct, total, timestamp
     * @return bool True on success, false on failure
     */
    public function saveScore($scoreData) {
        try {
            if (!$this->connect()) {
                return false;
            }
            
            // If we're using SQLite
            if ($this->useSQLite && $this->pdo) {
                // Get or create the quiz
                $quizDbId = $this->getOrCreateQuiz(
                    $scoreData['quiz'], 
                    $scoreData['topic'] ?? 'default'
                );
                
                // Save the score
                $stmt = $this->pdo->prepare("
                    INSERT INTO scores 
                        (quiz_id, user_id, name, email, score, correct, total, timestamp, date) 
                    VALUES 
                        (:quiz_id, :user_id, :name, :email, :score, :correct, :total, :timestamp, :date)
                ");
                
                $stmt->execute([
                    ':quiz_id' => $quizDbId,
                    ':user_id' => $scoreData['userId'] ?? uniqid('user_'),
                    ':name' => $scoreData['name'] ?? null,
                    ':email' => $scoreData['email'] ?? null,
                    ':score' => $scoreData['score'],
                    ':correct' => $scoreData['correct'],
                    ':total' => $scoreData['total'],
                    ':timestamp' => $scoreData['timestamp'] ?? time(),
                    ':date' => isset($scoreData['timestamp']) ? date('Y-m-d H:i:s', $scoreData['timestamp']) : date('Y-m-d H:i:s')
                ]);
                
                return true;
            } else {
                // For file storage
                if ($this->inTransaction) {
                    // If in a transaction, buffer the score
                    $this->transactionScores[] = $scoreData;
                    return true;
                } else {
                    // Otherwise save directly to file
                    return $this->saveScoreToFile($scoreData);
                }
            }
            
        } catch (Exception $e) {
            error_log("Error saving score: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get statistics for a quiz
     * 
     * @param string $quizId Quiz identifier
     * @param string $topic Quiz topic
     * @return array|false Statistics array or false on failure
     */
    public function getQuizStats($quizId, $topic = 'default') {
        try {
            if (!$this->pdo) {
                if (!$this->connect()) {
                    return false;
                }
            }
            
            // Get the quiz ID
            $stmt = $this->pdo->prepare("SELECT id FROM quizzes WHERE quiz_id = :quiz_id AND topic = :topic");
            $stmt->execute([
                ':quiz_id' => $quizId,
                ':topic' => $topic
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If quiz doesn't exist, return default stats
            if (!$result) {
                return [
                    'quizId' => $quizId,
                    'topic' => $topic,
                    'totalAttempts' => 0,
                    'averageScore' => 0,
                    'highScore' => 0,
                    'distribution' => [
                        '0-20' => 0,
                        '21-40' => 0,
                        '41-60' => 0,
                        '61-80' => 0,
                        '81-100' => 0
                    ],
                    'recentScores' => []
                ];
            }
            
            $quizDbId = $result['id'];
            
            // Get total attempts
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM scores WHERE quiz_id = :quiz_id");
            $stmt->execute([':quiz_id' => $quizDbId]);
            $totalAttempts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get average score
            $stmt = $this->pdo->prepare("SELECT AVG(score) as average FROM scores WHERE quiz_id = :quiz_id");
            $stmt->execute([':quiz_id' => $quizDbId]);
            $averageScore = round($stmt->fetch(PDO::FETCH_ASSOC)['average'] * 100);
            
            // Get high score
            $stmt = $this->pdo->prepare("SELECT MAX(score) as high FROM scores WHERE quiz_id = :quiz_id");
            $stmt->execute([':quiz_id' => $quizDbId]);
            $highScore = round($stmt->fetch(PDO::FETCH_ASSOC)['high'] * 100);
            
            // Calculate score distribution
            $distribution = [
                '0-20' => 0,
                '21-40' => 0,
                '41-60' => 0,
                '61-80' => 0,
                '81-100' => 0
            ];
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    SUM(CASE WHEN score <= 0.20 THEN 1 ELSE 0 END) as range1,
                    SUM(CASE WHEN score > 0.20 AND score <= 0.40 THEN 1 ELSE 0 END) as range2,
                    SUM(CASE WHEN score > 0.40 AND score <= 0.60 THEN 1 ELSE 0 END) as range3,
                    SUM(CASE WHEN score > 0.60 AND score <= 0.80 THEN 1 ELSE 0 END) as range4,
                    SUM(CASE WHEN score > 0.80 THEN 1 ELSE 0 END) as range5
                FROM scores 
                WHERE quiz_id = :quiz_id
            ");
            $stmt->execute([':quiz_id' => $quizDbId]);
            $dist = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $distribution['0-20'] = (int)$dist['range1'];
            $distribution['21-40'] = (int)$dist['range2'];
            $distribution['41-60'] = (int)$dist['range3'];
            $distribution['61-80'] = (int)$dist['range4'];
            $distribution['81-100'] = (int)$dist['range5'];
            
            // Get recent scores
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.score * 100 as percentage, 
                    s.date,
                    s.name,
                    s.correct,
                    s.total
                FROM scores s
                WHERE s.quiz_id = :quiz_id 
                ORDER BY s.timestamp DESC 
                LIMIT 20
            ");
            $stmt->execute([':quiz_id' => $quizDbId]);
            $recentScores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format scores
            $formattedScores = [];
            foreach ($recentScores as $score) {
                $formattedScores[] = [
                    'percentage' => round($score['percentage']),
                    'date' => $score['date'],
                    'name' => $score['name'],
                    'correct' => $score['correct'],
                    'total' => $score['total']
                ];
            }
            
            // Get high scores
            $stmt = $this->pdo->prepare("
                SELECT 
                    s.score * 100 as percentage, 
                    s.date,
                    s.name,
                    s.correct,
                    s.total
                FROM scores s
                WHERE s.quiz_id = :quiz_id 
                ORDER BY s.score DESC, s.timestamp DESC
                LIMIT 10
            ");
            $stmt->execute([':quiz_id' => $quizDbId]);
            $highScores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format high scores
            $formattedHighScores = [];
            foreach ($highScores as $score) {
                $formattedHighScores[] = [
                    'percentage' => round($score['percentage']),
                    'date' => $score['date'],
                    'name' => $score['name'],
                    'correct' => $score['correct'],
                    'total' => $score['total']
                ];
            }
            
            // Return statistics
            return [
                'quizId' => $quizId,
                'topic' => $topic,
                'totalAttempts' => $totalAttempts,
                'averageScore' => $averageScore,
                'highScore' => $highScore,
                'distribution' => $distribution,
                'recentScores' => $formattedScores,
                'highScores' => $formattedHighScores
            ];
            
        } catch (PDOException $e) {
            error_log("Error getting quiz stats: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * List all quizzes in the database
     * 
     * @return array|false Array of quizzes or false on failure
     */
    public function listQuizzes() {
        try {
            if (!$this->pdo) {
                if (!$this->connect()) {
                    return false;
                }
            }
            
            $stmt = $this->pdo->query("
                SELECT 
                    q.quiz_id, 
                    q.topic, 
                    q.title,
                    COUNT(s.id) as attempt_count,
                    AVG(s.score) * 100 as average_score,
                    MAX(s.score) * 100 as high_score,
                    MAX(s.timestamp) as last_attempt
                FROM quizzes q
                LEFT JOIN scores s ON q.id = s.quiz_id
                GROUP BY q.id
                ORDER BY q.topic, q.quiz_id
            ");
            
            $quizzes = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $quizzes[] = [
                    'quizId' => $row['quiz_id'],
                    'topic' => $row['topic'],
                    'title' => $row['title'],
                    'attemptCount' => (int)$row['attempt_count'],
                    'averageScore' => round($row['average_score']),
                    'highScore' => round($row['high_score']),
                    'lastAttempt' => $row['last_attempt'] ? date('Y-m-d H:i:s', $row['last_attempt']) : null
                ];
            }
            
            return $quizzes;
            
        } catch (PDOException $e) {
            error_log("Error listing quizzes: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Get a database connection
 * 
 * @return PDO|false Database connection or false on failure
 */
function getDbConnection() {
    $dbFile = __DIR__ . '/scores/quiz_scores.db';
    $dbDir = dirname($dbFile);
    
    // Ensure the directory exists
    if (!is_dir($dbDir)) {
        if (!mkdir($dbDir, 0755, true)) {
            error_log("Failed to create database directory: {$dbDir}");
            return false;
        }
    }
    
    try {
        // Create/connect to the database
        $pdo = new PDO("sqlite:{$dbFile}");
        
        // Set error mode to exceptions
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Set proper permissions for the database file (if possible)
        if (file_exists($dbFile)) {
            if (!@chmod($dbFile, 0666)) {
                // Log warning but don't fail - the database might still work
                error_log("Warning: Could not set permissions on database file: {$dbFile}");
            }
        }
        
        // Initialize tables
        initDbTables($pdo);
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Initialize database tables if they don't exist
 * 
 * @param PDO $pdo Database connection
 * @return bool Success or failure
 */
function initDbTables($pdo) {
    try {
        // Create quizzes table
        $pdo->exec("CREATE TABLE IF NOT EXISTS quizzes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            quiz_id TEXT NOT NULL,
            topic TEXT NOT NULL,
            title TEXT,
            UNIQUE(quiz_id, topic)
        )");
        
        // Create scores table
        $pdo->exec("CREATE TABLE IF NOT EXISTS scores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            quiz_id INTEGER NOT NULL,
            user_id TEXT NOT NULL,
            name TEXT,
            email TEXT,
            score INTEGER NOT NULL,
            total_questions INTEGER NOT NULL,
            percentage INTEGER NOT NULL,
            timestamp INTEGER NOT NULL,
            date TEXT NOT NULL,
            FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
        )");
        
        // Create index on quiz_id for faster lookups
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_scores_quiz_id ON scores(quiz_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_scores_timestamp ON scores(timestamp)");
        
        return true;
    } catch (PDOException $e) {
        error_log("Failed to initialize tables: " . $e->getMessage());
        return false;
    }
}

/**
 * Get or create a quiz record
 * 
 * @param PDO $pdo Database connection
 * @param string $quizId Quiz identifier
 * @param string $topic Quiz topic
 * @param string $title Quiz title (optional)
 * @return int The quiz record ID
 */
function getOrCreateQuiz($pdo, $quizId, $topic, $title = null) {
    // First try to find the quiz
    $stmt = $pdo->prepare("SELECT id FROM quizzes WHERE quiz_id = :quiz_id AND topic = :topic");
    $stmt->execute([
        ':quiz_id' => $quizId,
        ':topic' => $topic
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If quiz exists, return its ID
    if ($result) {
        return $result['id'];
    }
    
    // Otherwise, create a new quiz record
    $stmt = $pdo->prepare("INSERT INTO quizzes (quiz_id, topic, title) VALUES (:quiz_id, :topic, :title)");
    $stmt->execute([
        ':quiz_id' => $quizId,
        ':topic' => $topic,
        ':title' => $title
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * Save a quiz score to the database
 * 
 * @param array $scoreData Score data with keys: quizId, topic, quizTitle, score, totalQuestions, userId
 * @return bool True on success, false on failure
 */
function saveScore($scoreData) {
    try {
        $pdo = getDbConnection();
        if (!$pdo) {
            return false;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Get or create the quiz
        $quizDbId = getOrCreateQuiz(
            $pdo, 
            $scoreData['quizId'], 
            $scoreData['topic'] ?? 'default',
            $scoreData['quizTitle'] ?? null
        );
        
        // Calculate percentage
        $percentage = ($scoreData['totalQuestions'] > 0) 
            ? round(($scoreData['score'] / $scoreData['totalQuestions']) * 100) 
            : 0;
        
        // Prepare timestamp and date
        $timestamp = $scoreData['timestamp'] ?? time();
        $date = $scoreData['date'] ?? date('Y-m-d H:i:s', $timestamp);
        
        // Save the score
        $stmt = $pdo->prepare("
            INSERT INTO scores 
                (quiz_id, user_id, name, email, score, total_questions, percentage, timestamp, date) 
            VALUES 
                (:quiz_id, :user_id, :name, :email, :score, :total_questions, :percentage, :timestamp, :date)
        ");
        
        $stmt->execute([
            ':quiz_id' => $quizDbId,
            ':user_id' => $scoreData['userId'] ?? uniqid('user_'),
            ':name' => $scoreData['name'] ?? null,
            ':email' => $scoreData['email'] ?? null,
            ':score' => $scoreData['score'],
            ':total_questions' => $scoreData['totalQuestions'],
            ':percentage' => $percentage,
            ':timestamp' => $timestamp,
            ':date' => $date
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        return true;
        
    } catch (PDOException $e) {
        // Roll back transaction on error
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error saving score: " . $e->getMessage());
        return false;
    }
}

/**
 * Get statistics for a quiz
 * 
 * @param string $quizId Quiz identifier
 * @param string $topic Quiz topic
 * @return array|false Statistics array or false on failure
 */
function getQuizStats($quizId, $topic = 'default') {
    try {
        $pdo = getDbConnection();
        if (!$pdo) {
            return false;
        }
        
        // Get the quiz ID
        $stmt = $pdo->prepare("SELECT id FROM quizzes WHERE quiz_id = :quiz_id AND topic = :topic");
        $stmt->execute([
            ':quiz_id' => $quizId,
            ':topic' => $topic
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If quiz doesn't exist, return default stats
        if (!$result) {
            return [
                'quizId' => $quizId,
                'topic' => $topic,
                'totalAttempts' => 0,
                'averageScore' => 0,
                'highScore' => 0,
                'distribution' => [
                    '0-20' => 0,
                    '21-40' => 0,
                    '41-60' => 0,
                    '61-80' => 0,
                    '81-100' => 0
                ],
                'recentScores' => [],
                'highScores' => []
            ];
        }
        
        $quizDbId = $result['id'];
        
        // Get total attempts
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM scores WHERE quiz_id = :quiz_id");
        $stmt->execute([':quiz_id' => $quizDbId]);
        $totalAttempts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get average score
        $stmt = $pdo->prepare("SELECT AVG(percentage) as average FROM scores WHERE quiz_id = :quiz_id");
        $stmt->execute([':quiz_id' => $quizDbId]);
        $averageScore = round($stmt->fetch(PDO::FETCH_ASSOC)['average']);
        
        // Get high score
        $stmt = $pdo->prepare("SELECT MAX(percentage) as high FROM scores WHERE quiz_id = :quiz_id");
        $stmt->execute([':quiz_id' => $quizDbId]);
        $highScore = (int)$stmt->fetch(PDO::FETCH_ASSOC)['high'];
        
        // Get score distribution
        $distribution = [
            '0-20' => 0,
            '21-40' => 0,
            '41-60' => 0,
            '61-80' => 0,
            '81-100' => 0
        ];
        
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN percentage <= 20 THEN 1 ELSE 0 END) as range1,
                SUM(CASE WHEN percentage > 20 AND percentage <= 40 THEN 1 ELSE 0 END) as range2,
                SUM(CASE WHEN percentage > 40 AND percentage <= 60 THEN 1 ELSE 0 END) as range3,
                SUM(CASE WHEN percentage > 60 AND percentage <= 80 THEN 1 ELSE 0 END) as range4,
                SUM(CASE WHEN percentage > 80 THEN 1 ELSE 0 END) as range5
            FROM scores 
            WHERE quiz_id = :quiz_id
        ");
        $stmt->execute([':quiz_id' => $quizDbId]);
        $dist = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $distribution['0-20'] = (int)$dist['range1'];
        $distribution['21-40'] = (int)$dist['range2'];
        $distribution['41-60'] = (int)$dist['range3'];
        $distribution['61-80'] = (int)$dist['range4'];
        $distribution['81-100'] = (int)$dist['range5'];
        
        // Get recent scores
        $stmt = $pdo->prepare("
            SELECT 
                percentage, 
                date, 
                name, 
                score as correct,
                total_questions as total
            FROM scores 
            WHERE quiz_id = :quiz_id 
            ORDER BY timestamp DESC 
            LIMIT 20
        ");
        $stmt->execute([':quiz_id' => $quizDbId]);
        $recentScores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format recent scores
        $formattedScores = [];
        foreach ($recentScores as $score) {
            $formattedScores[] = [
                'percentage' => (int)$score['percentage'],
                'date' => $score['date'],
                'name' => $score['name'],
                'correct' => (int)$score['correct'],
                'total' => (int)$score['total']
            ];
        }
        
        // Get high scores
        $stmt = $pdo->prepare("
            SELECT 
                percentage, 
                date, 
                name, 
                score as correct,
                total_questions as total
            FROM scores 
            WHERE quiz_id = :quiz_id 
            ORDER BY percentage DESC, timestamp DESC
            LIMIT 10
        ");
        $stmt->execute([':quiz_id' => $quizDbId]);
        $highScores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format high scores
        $formattedHighScores = [];
        foreach ($highScores as $score) {
            $formattedHighScores[] = [
                'percentage' => (int)$score['percentage'],
                'date' => $score['date'],
                'name' => $score['name'],
                'correct' => (int)$score['correct'],
                'total' => (int)$score['total']
            ];
        }
        
        // Return statistics
        return [
            'quizId' => $quizId,
            'topic' => $topic,
            'totalAttempts' => $totalAttempts,
            'averageScore' => $averageScore,
            'highScore' => $highScore,
            'distribution' => $distribution,
            'recentScores' => $formattedScores,
            'highScores' => $formattedHighScores
        ];
        
    } catch (PDOException $e) {
        error_log("Error getting quiz stats: " . $e->getMessage());
        return false;
    }
}

/**
 * Seed the database with random scores
 * 
 * @param string $quizId Quiz identifier
 * @param string $topic Quiz topic
 * @param string $title Quiz title
 * @param int $count Number of scores to generate
 * @param float $minScore Minimum score as fraction (0.0 to 1.0)
 * @param float $maxScore Maximum score as fraction (0.0 to 1.0)
 * @return bool True on success, false on failure
 */
function seedQuizScores($quizId, $topic, $title, $count = 50, $minScore = 0.6, $maxScore = 1.0) {
    try {
        $pdo = getDbConnection();
        if (!$pdo) {
            return false;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Get or create the quiz
        $quizDbId = getOrCreateQuiz($pdo, $quizId, $topic, $title);
        
        // Generate random scores
        $now = time();
        $oneMonthAgo = $now - (30 * 24 * 60 * 60);
        
        // Random names for sample data
        $names = [
            'John Doe', 'Jane Smith', 'Alex Johnson', 'Taylor Swift', 'Chris Brown', 
            'Sam Wilson', 'Casey Jones', 'Jordan Lee', 'Robin Banks', 'Morgan Freeman'
        ];
        
        for ($i = 0; $i < $count; $i++) {
            // Random timestamp within the last month
            $timestamp = mt_rand($oneMonthAgo, $now);
            $date = date('Y-m-d H:i:s', $timestamp);
            
            // Random user ID and name
            $userId = uniqid('user_');
            $name = $names[array_rand($names)];
            
            // Random score between min and max
            $totalQuestions = mt_rand(10, 20);
            $scorePercentage = $minScore + (mt_rand(0, 1000) / 1000) * ($maxScore - $minScore);
            $score = round($scorePercentage * $totalQuestions);
            $percentage = round($scorePercentage * 100);
            
            // Save score
            $stmt = $pdo->prepare("
                INSERT INTO scores 
                    (quiz_id, user_id, name, email, score, total_questions, percentage, timestamp, date) 
                VALUES 
                    (:quiz_id, :user_id, :name, :email, :score, :total_questions, :percentage, :timestamp, :date)
            ");
            
            $stmt->execute([
                ':quiz_id' => $quizDbId,
                ':user_id' => $userId,
                ':name' => $name,
                ':email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
                ':score' => $score,
                ':total_questions' => $totalQuestions,
                ':percentage' => $percentage,
                ':timestamp' => $timestamp,
                ':date' => $date
            ]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        return true;
        
    } catch (PDOException $e) {
        // Roll back transaction on error
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error seeding scores: " . $e->getMessage());
        return false;
    }
}

/**
 * List all quizzes in the database
 * 
 * @return array|false Array of quizzes or false on failure
 */
function listQuizzes() {
    try {
        $pdo = getDbConnection();
        if (!$pdo) {
            return false;
        }
        
        $stmt = $pdo->query("
            SELECT 
                q.quiz_id, 
                q.topic, 
                q.title,
                COUNT(s.id) as attempt_count,
                AVG(s.percentage) as average_score,
                MAX(s.percentage) as high_score,
                MAX(s.timestamp) as last_attempt
            FROM quizzes q
            LEFT JOIN scores s ON q.id = s.quiz_id
            GROUP BY q.id
            ORDER BY q.topic, q.quiz_id
        ");
        
        $quizzes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $quizzes[] = [
                'quizId' => $row['quiz_id'],
                'topic' => $row['topic'],
                'title' => $row['title'] ?: ucwords(str_replace(['-', '_'], ' ', $row['quiz_id'])),
                'attemptCount' => (int)$row['attempt_count'],
                'averageScore' => $row['average_score'] ? round($row['average_score']) : 0,
                'highScore' => $row['high_score'] ? round($row['high_score']) : 0,
                'lastAttempt' => $row['last_attempt'] ? date('Y-m-d H:i:s', $row['last_attempt']) : null
            ];
        }
        
        return $quizzes;
        
    } catch (PDOException $e) {
        error_log("Error listing quizzes: " . $e->getMessage());
        return false;
    }
}
