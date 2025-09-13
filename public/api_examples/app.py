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