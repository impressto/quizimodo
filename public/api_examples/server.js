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