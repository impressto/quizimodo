// scoreService.ts - Functions for interacting with the score API

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

// Function to save a quiz score to the backend
export const saveQuizScore = async (
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

// Function to fetch quiz statistics
export const fetchQuizStats = async (quizId: string) => {
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
