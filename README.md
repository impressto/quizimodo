
# Quizzimoto

  

[Live Demo](https://impressto.ca/teamfortress_quizzes.php) | [Repository](https://github.com/impressto/quizzimoto)

  

Quizzimoto is a **React-based quiz application** designed to make building and playing quizzes straightforward, engaging, and effective.

  

It includes interactive features such as **answer streaks, animations, and automatically generated cheat sheets** to enhance both the learning experience and long-term retention.

  

---

  

<img  width="738"  height="604"  alt="quiz_example"  src="https://github.com/user-attachments/assets/59d0d1a2-194b-4c2a-a522-16962cc49bc2"  />

  
  

## 🚀 Features

  

-  **Dynamic Quiz Experience** – clean UI for answering multiple-choice questions

-  **Streaks & Animations** – visual feedback when users answer multiple questions correctly in a row

-  **Cheat Sheet Export** – automatically generate and save a summary of all questions and correct answers at the end of each quiz

-  **Reusable Components** – quizzes can be easily customized for different subjects

-  **Modern Front-End Stack** – designed with maintainability and scalability in mind

  

---

  

## 🛠 Tech Stack

  

-  **React** – component-based front-end framework

-  **React Hooks** – state management and interactivity

-  **JavaScript (ES6+)** – modern language features

-  **CSS / Animations / Framer Motion** (if used) – smooth UI effects

-  **Node.js & npm** – development environment and package management

  

---

  

## 📦 Installation & Setup

  

Clone the repository:

```bash

git clone  https://github.com/impressto/quizzimoto.git
cd quizzimoto
``````bash

Install dependencies:

```bash
npm install
```
 
Run the  app  locally:
```bash
npm run dev
```
  
The app will be available at http://localhost:3000 by default.

## Build for production

  
```bash
npm run build
```
  This creates an optimized production build in the /build folder.

---

## 📊 Setting Up Score Tracking API

Quizzimoto supports optional score tracking to allow users to compare their performance with others. You can implement the backend API using PHP, Node.js, or Python (or whatever works for you).

### Environment Configuration

Add these variables to your `.env` file:

```
VITE_API_BASE_URL=https://your-domain.com/api  # Base URL for your API endpoints
```

### PHP Implementation

1. Create a directory for storing scores:

```bash
mkdir -p public/api/scores
chmod 755 public/api/scores
```

2. Create `public/api/save-score.php`:

```php
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
```

3. Create `public/api/quiz-stats.php`:

```php
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
```

### Node.js Implementation

1. Create a new directory for your API:

```bash
mkdir -p api
```

2. Initialize a Node.js project:

```bash
cd api
npm init -y
npm install express cors body-parser fs-extra
```

3. Create `api/server.js`:

```javascript
const express = require('express');
const cors = require('cors');
const bodyParser = require('body-parser');
const fs = require('fs-extra');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(bodyParser.json());

// Ensure scores directory exists
const scoresDir = path.join(__dirname, 'scores');
fs.ensureDirSync(scoresDir);

// Save score endpoint
app.post('/save-score', async (req, res) => {
  try {
    const { quizId, score, totalQuestions, topic, quizTitle, anonymousId } = req.body;
    
    // Validate required fields
    if (!quizId || score === undefined || !totalQuestions) {
      return res.status(400).json({ error: 'Missing required fields' });
    }
    
    // Create score record
    const scoreRecord = {
      quizId,
      topic: topic || '',
      quizTitle: quizTitle || '',
      score,
      totalQuestions,
      percentage: Math.round((score / totalQuestions) * 100),
      userId: anonymousId || `anon_${Date.now()}`,
      timestamp: Date.now(),
      date: new Date().toISOString()
    };
    
    // Get file path for this quiz's scores
    const scoresFile = path.join(scoresDir, `${quizId}_scores.json`);
    
    // Load existing scores or create empty array
    let scores = [];
    if (await fs.pathExists(scoresFile)) {
      const fileContent = await fs.readFile(scoresFile, 'utf8');
      scores = fileContent ? JSON.parse(fileContent) : [];
    }
    
    // Add new score
    scores.push(scoreRecord);
    
    // Keep only the most recent scores
    const maxScores = 1000;
    if (scores.length > maxScores) {
      scores = scores.slice(-maxScores);
    }
    
    // Save scores back to file
    await fs.writeFile(scoresFile, JSON.stringify(scores, null, 2));
    
    res.status(200).json({ success: true });
  } catch (error) {
    console.error('Error saving score:', error);
    res.status(500).json({ error: 'Failed to save score' });
  }
});

// Get quiz stats endpoint
app.get('/quiz-stats', async (req, res) => {
  try {
    const { quizId } = req.query;
    
    if (!quizId) {
      return res.status(400).json({ error: 'Missing quizId parameter' });
    }
    
    const scoresFile = path.join(scoresDir, `${quizId}_scores.json`);
    
    // Return empty stats if file doesn't exist
    if (!await fs.pathExists(scoresFile)) {
      return res.json({
        quizId,
        totalAttempts: 0,
        averageScore: 0,
        highScore: 0,
        distribution: {
          '0-20': 0,
          '21-40': 0,
          '41-60': 0,
          '61-80': 0,
          '81-100': 0
        },
        recentScores: []
      });
    }
    
    // Load scores
    const fileContent = await fs.readFile(scoresFile, 'utf8');
    const scores = JSON.parse(fileContent);
    
    // Calculate statistics
    const totalAttempts = scores.length;
    let totalPercentage = 0;
    let highScore = 0;
    
    scores.forEach(score => {
      totalPercentage += score.percentage;
      highScore = Math.max(highScore, score.percentage);
    });
    
    const averageScore = totalAttempts > 0 ? Math.round(totalPercentage / totalAttempts) : 0;
    
    // Get recent scores
    const recentScores = scores.slice(-10).map(score => ({
      percentage: score.percentage,
      date: score.date
    }));
    
    // Calculate score distribution
    const distribution = {
      '0-20': 0,
      '21-40': 0,
      '41-60': 0,
      '61-80': 0,
      '81-100': 0
    };
    
    scores.forEach(score => {
      const percentage = score.percentage;
      if (percentage <= 20) distribution['0-20']++;
      else if (percentage <= 40) distribution['21-40']++;
      else if (percentage <= 60) distribution['41-60']++;
      else if (percentage <= 80) distribution['61-80']++;
      else distribution['81-100']++;
    });
    
    res.json({
      quizId,
      totalAttempts,
      averageScore,
      highScore,
      distribution,
      recentScores
    });
  } catch (error) {
    console.error('Error fetching quiz stats:', error);
    res.status(500).json({ error: 'Failed to fetch quiz statistics' });
  }
});

// Start server
app.listen(PORT, () => {
  console.log(`Score API server running on port ${PORT}`);
});
```

4. Run the server:

```bash
node server.js
```

### Python Implementation (with Flask)

1. Set up a Python virtual environment:

```bash
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
pip install flask flask-cors
```

2. Create `app.py`:

```python
import os
import json
import time
from datetime import datetime
from flask import Flask, request, jsonify
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

# Ensure scores directory exists
SCORES_DIR = os.path.join(os.path.dirname(__file__), 'scores')
os.makedirs(SCORES_DIR, exist_ok=True)

@app.route('/save-score', methods=['POST', 'OPTIONS'])
def save_score():
    if request.method == 'OPTIONS':
        return '', 200
        
    data = request.json
    
    # Validate required fields
    if not data or 'quizId' not in data or 'score' not in data or 'totalQuestions' not in data:
        return jsonify({'error': 'Missing required fields'}), 400
    
    # Extract and sanitize data
    quiz_id = ''.join(c for c in data['quizId'] if c.isalnum() or c in '-_')
    score = int(data['score'])
    total_questions = int(data['totalQuestions'])
    timestamp = int(time.time())
    
    # Optional fields
    topic = ''.join(c for c in data.get('topic', '') if c.isalnum() or c in '-_')
    quiz_title = data.get('quizTitle', '')[:100]
    
    # Anonymize user
    user_id = data.get('anonymousId', f'anon_{int(time.time())}')
    
    # Create score record
    score_record = {
        'quizId': quiz_id,
        'topic': topic,
        'quizTitle': quiz_title,
        'score': score,
        'totalQuestions': total_questions,
        'percentage': round((score / total_questions) * 100) if total_questions > 0 else 0,
        'userId': user_id,
        'timestamp': timestamp,
        'date': datetime.fromtimestamp(timestamp).strftime('%Y-%m-%d %H:%M:%S')
    }
    
    # Get file path for this quiz's scores
    scores_file = os.path.join(SCORES_DIR, f"{quiz_id}_scores.json")
    
    # Load existing scores or create empty list
    scores = []
    if os.path.exists(scores_file):
        try:
            with open(scores_file, 'r') as f:
                file_content = f.read()
                if file_content:
                    scores = json.loads(file_content)
        except (json.JSONDecodeError, IOError):
            pass
    
    # Add new score
    scores.append(score_record)
    
    # Keep only the most recent scores
    max_scores = 1000
    if len(scores) > max_scores:
        scores = scores[-max_scores:]
    
    # Save scores back to file
    try:
        with open(scores_file, 'w') as f:
            json.dump(scores, f, indent=2)
        return jsonify({'success': True})
    except IOError:
        return jsonify({'error': 'Failed to save score'}), 500

@app.route('/quiz-stats', methods=['GET'])
def quiz_stats():
    quiz_id = request.args.get('quizId')
    
    if not quiz_id:
        return jsonify({'error': 'Missing quizId parameter'}), 400
    
    # Sanitize quiz_id
    quiz_id = ''.join(c for c in quiz_id if c.isalnum() or c in '-_')
    scores_file = os.path.join(SCORES_DIR, f"{quiz_id}_scores.json")
    
    # Return empty stats if file doesn't exist
    if not os.path.exists(scores_file):
        return jsonify({
            'quizId': quiz_id,
            'totalAttempts': 0,
            'averageScore': 0,
            'highScore': 0,
            'distribution': {
                '0-20': 0,
                '21-40': 0,
                '41-60': 0,
                '61-80': 0,
                '81-100': 0
            },
            'recentScores': []
        })
    
    # Load scores
    try:
        with open(scores_file, 'r') as f:
            scores = json.load(f)
    except (json.JSONDecodeError, IOError):
        scores = []
    
    # Calculate statistics
    total_attempts = len(scores)
    total_percentage = 0
    high_score = 0
    
    for score in scores:
        total_percentage += score['percentage']
        high_score = max(high_score, score['percentage'])
    
    average_score = round(total_percentage / total_attempts) if total_attempts > 0 else 0
    
    # Get recent scores
    recent_scores = [{
        'percentage': score['percentage'],
        'date': score['date']
    } for score in scores[-10:]]
    
    # Calculate score distribution
    distribution = {
        '0-20': 0,
        '21-40': 0,
        '41-60': 0,
        '61-80': 0,
        '81-100': 0
    }
    
    for score in scores:
        percentage = score['percentage']
        if percentage <= 20:
            distribution['0-20'] += 1
        elif percentage <= 40:
            distribution['21-40'] += 1
        elif percentage <= 60:
            distribution['41-60'] += 1
        elif percentage <= 80:
            distribution['61-80'] += 1
        else:
            distribution['81-100'] += 1
    
    return jsonify({
        'quizId': quiz_id,
        'totalAttempts': total_attempts,
        'averageScore': average_score,
        'highScore': high_score,
        'distribution': distribution,
        'recentScores': recent_scores
    })

if __name__ == '__main__':
    app.run(debug=True, port=5000)
```

3. Run the Flask app:

```bash
python app.py
```

### Frontend Integration

The quiz application already includes built-in support for score tracking. The main components that handle this functionality are:

1. `src/scoreService.ts` - Contains functions for saving scores and fetching statistics
2. `src/QuizResult.tsx` - Displays the quiz results and statistics

To enable or disable score tracking, simply set the `VITE_API_BASE_URL` environment variable in your `.env` file.

```
VITE_API_BASE_URL=/api  # For local development with API in /public/api
# or
VITE_API_BASE_URL=https://your-domain.com/api  # For production
```