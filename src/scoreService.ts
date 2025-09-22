// scoreService.ts - Functions for interacting with the score API
import { isStaticMode, getQuizzesBaseUrl } from './config';

export interface QuizStats {
  quizId: string;
  totalAttempts: number;
  averageScore: number;
  highScore: number;
  distribution: {
    '0-20': number;
    '21-40': number;
    '41-60': number;
    '61-80': number;
    '81-100': number;
  };
  recentScores: Array<{
    percentage: number;
    date: string;
  }>;
}

// Function to generate a unique anonymous ID per user session
const getAnonymousId = () => {
  // Try to get from session storage first
  let anonymousId = sessionStorage.getItem('quiz_anonymous_id');
  
  // If not found, create new one
  if (!anonymousId) {
    anonymousId = 'anon_' + Math.random().toString(36).substring(2, 15);
    sessionStorage.setItem('quiz_anonymous_id', anonymousId);
  }
  
  return anonymousId;
};

// Static mode: Save scores to localStorage
const saveScoreStatic = (
  quizId: string,
  score: number,
  totalQuestions: number,
  topic: string,
  quizTitle: string
) => {
  try {
    const percentage = Math.round((score / totalQuestions) * 100);
    const scoreData = {
      quizId,
      topic,
      quizTitle,
      score,
      totalQuestions,
      percentage,
      anonymousId: getAnonymousId(),
      timestamp: new Date().toISOString(),
    };

    // Get existing scores
    const existingScores = JSON.parse(localStorage.getItem('quiz_scores') || '[]');
    
    // Add new score
    existingScores.push(scoreData);
    
    // Keep only last 50 scores to prevent localStorage bloat
    if (existingScores.length > 50) {
      existingScores.splice(0, existingScores.length - 50);
    }
    
    // Save back to localStorage
    localStorage.setItem('quiz_scores', JSON.stringify(existingScores));
    
    return true;
  } catch (error) {
    console.error('Error saving score to localStorage:', error);
    return false;
  }
};

// Server mode: Save scores to backend
const saveScoreServer = async (
  quizId: string,
  score: number,
  totalQuestions: number,
  topic: string,
  quizTitle: string
) => {
  // Get base URL for API calls
  const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api';
  
  try {
    const response = await fetch(`${apiBaseUrl}/save-score.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        quizId,
        topic,
        quizTitle,
        score,
        totalQuestions,
        anonymousId: getAnonymousId(),
      }),
    });
    
    if (!response.ok) {
      // If error, just log it - we don't want to bother the user about stats
      console.error('Failed to save quiz score:', await response.text());
      return false;
    }
    
    return true;
  } catch (error) {
    console.error('Error saving quiz score:', error);
    return false;
  }
};

// Main function to save a quiz score (uses appropriate method based on mode)
export const saveQuizScore = async (
  quizId: string,
  score: number,
  totalQuestions: number,
  topic: string,
  quizTitle: string
) => {
  if (isStaticMode()) {
    return saveScoreStatic(quizId, score, totalQuestions, topic, quizTitle);
  } else {
    return await saveScoreServer(quizId, score, totalQuestions, topic, quizTitle);
  }
};

// Static mode: Fetch stats from localStorage and static file
const fetchQuizStatsStatic = async (quizId: string): Promise<QuizStats | null> => {
  try {
    // First, try to load base stats from static file
    const baseUrl = getQuizzesBaseUrl();
    let baseStats = null;
    
    try {
      const response = await fetch(`${baseUrl}/../quiz-stats.json`);
      if (response.ok) {
        const allStats = await response.json();
        baseStats = allStats[quizId];
      }
    } catch (error) {
      console.log('No static stats file found, using defaults');
    }
    
    // Get user's scores from localStorage
    const userScores = JSON.parse(localStorage.getItem('quiz_scores') || '[]')
      .filter((score: any) => score.quizId === quizId);
    
    // Calculate stats from user's local scores
    let localStats = {
      totalAttempts: 0,
      averageScore: 0,
      highScore: 0,
      distribution: {
        '0-20': 0,
        '21-40': 0,
        '41-60': 0,
        '61-80': 0,
        '81-100': 0,
      },
      recentScores: [] as Array<{ percentage: number; date: string }>,
    };
    
    if (userScores.length > 0) {
      localStats.totalAttempts = userScores.length;
      localStats.averageScore = userScores.reduce((sum: number, score: any) => sum + score.percentage, 0) / userScores.length;
      localStats.highScore = Math.max(...userScores.map((score: any) => score.percentage));
      
      // Calculate distribution
      userScores.forEach((score: any) => {
        const percentage = score.percentage;
        if (percentage <= 20) localStats.distribution['0-20']++;
        else if (percentage <= 40) localStats.distribution['21-40']++;
        else if (percentage <= 60) localStats.distribution['41-60']++;
        else if (percentage <= 80) localStats.distribution['61-80']++;
        else localStats.distribution['81-100']++;
      });
      
      // Get recent scores (last 10)
      localStats.recentScores = userScores
        .slice(-10)
        .map((score: any) => ({
          percentage: score.percentage,
          date: new Date(score.timestamp).toISOString().split('T')[0],
        }));
    }
    
    // Merge with base stats if available
    if (baseStats) {
      return {
        quizId,
        totalAttempts: baseStats.totalAttempts + localStats.totalAttempts,
        averageScore: localStats.totalAttempts > 0 
          ? ((baseStats.averageScore * baseStats.totalAttempts) + (localStats.averageScore * localStats.totalAttempts)) / (baseStats.totalAttempts + localStats.totalAttempts)
          : baseStats.averageScore,
        highScore: Math.max(baseStats.highScore, localStats.highScore),
        distribution: {
          '0-20': baseStats.distribution['0-20'] + localStats.distribution['0-20'],
          '21-40': baseStats.distribution['21-40'] + localStats.distribution['21-40'],
          '41-60': baseStats.distribution['41-60'] + localStats.distribution['41-60'],
          '61-80': baseStats.distribution['61-80'] + localStats.distribution['61-80'],
          '81-100': baseStats.distribution['81-100'] + localStats.distribution['81-100'],
        },
        recentScores: localStats.recentScores,
      };
    }
    
    // Return only local stats if no base stats available
    return {
      quizId,
      ...localStats,
    };
  } catch (error) {
    console.error('Error fetching static quiz stats:', error);
    return null;
  }
};

// Server mode: Fetch stats from backend
const fetchQuizStatsServer = async (quizId: string): Promise<QuizStats | null> => {
  // Get base URL for API calls
  const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api';
  
  try {
    const response = await fetch(`${apiBaseUrl}/quiz-stats.php?quizId=${encodeURIComponent(quizId)}`);
    
    if (!response.ok) {
      console.error('Failed to fetch quiz statistics');
      return null;
    }
    
    return await response.json();
  } catch (error) {
    console.error('Error fetching quiz statistics:', error);
    return null;
  }
};

// Main function to fetch quiz statistics (uses appropriate method based on mode)
export const fetchQuizStats = async (quizId: string): Promise<QuizStats | null> => {
  if (isStaticMode()) {
    return await fetchQuizStatsStatic(quizId);
  } else {
    return await fetchQuizStatsServer(quizId);
  }
};
