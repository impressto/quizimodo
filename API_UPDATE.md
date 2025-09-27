# Quiz Stats API Update

## Changes Made

We've updated the quiz statistics handling to correctly separate statistics by both quiz ID AND topic. This fixes an issue where quizzes with the same ID but in different topics (e.g., "basics" in both Python and Web-RTC topics) were sharing the same statistics.

### Key Changes:

1. **API Endpoints**:
   - Added `topic` parameter to `/quiz-stats` endpoint
   - Added topic to score file naming in `/save-score` endpoint
   - File naming now uses pattern: `{topic}_{quizId}_scores.json`

2. **TypeScript Client**:
   - Updated `fetchQuizStats()` and `fetchQuizStatsServer()` to accept topic parameter
   - Enhanced static mode to look for topic-prefixed keys in stats JSON

3. **Server Implementations**:
   - Updated all server implementations (PHP, Python, Node.js) for consistency
   - Added default topic ("default") when topic is not provided
   - Updated seed-scores.php script to generate scores with the topic-prefixed filenames

## Usage

### Frontend
```typescript
// Now includes topic parameter
const stats = await fetchQuizStats(quizId, topic); 
```

### API Endpoints

#### Save Score
POST to `/save-score` with:
```json
{
  "quizId": "quiz-name",
  "topic": "category-name",
  "score": 8,
  "totalQuestions": 10,
  "quizTitle": "Quiz Title"
}
```

#### Get Stats
GET `/quiz-stats?quizId=quiz-name&topic=category-name`

## Migration Notes

Existing scores will still be available under the default topic name. New scores will be saved with the proper topic separation.

### Seeding Data

The seed-scores.php script has been updated to generate topic-prefixed score files. You can run it to generate sample data:

```bash
# From the api_examples directory:
php seed-scores.php           # Generate scores for new quizzes only
php seed-scores.php --force   # Generate scores for all quizzes, overwriting existing data
```

This will create files in the format `{topic}_{quizId}_scores.json`, `{topic}_{quizId}_summary.json`, and `{topic}_{quizId}_recent.json`.
