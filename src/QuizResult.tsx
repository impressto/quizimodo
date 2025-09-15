import { useState, useEffect } from 'react';
import './QuizResult.css';
import './QuizStats.css';
import './CodeStyles.css';
import type { QuizData } from './types';
import { saveQuizScore, fetchQuizStats, type QuizStats } from './scoreService';
import { getTopicFromUrl } from './config';
import { renderHTML, stripHtmlTags } from './utils';

interface QuizResultProps {
  score: number;
  totalQuestions: number;
  quizTitle?: string; // Quiz title for sharing
  quizData?: QuizData; // Full quiz data for cheat sheet
  onRestart: () => void;
  onChooseNewQuiz?: () => void; // Optional callback to choose a new quiz
  onNextQuiz?: () => void; // Optional callback to go to next quiz
  nextQuizTitle?: string; // Optional title for next quiz
}

const QuizResult = ({
  score,
  totalQuestions,
  quizTitle = 'Quiz',
  quizData,
  onRestart,
  onChooseNewQuiz,
  onNextQuiz,
  nextQuizTitle
}: QuizResultProps) => {
  const percentage = Math.round((score / totalQuestions) * 100);
  const [showCopiedMessage, setShowCopiedMessage] = useState(false);
  const [quizStats, setQuizStats] = useState<QuizStats | null>(null);
  const [loadingStats, setLoadingStats] = useState(false);
  const [scoreSaved, setScoreSaved] = useState(false);
  
  // Generate share message
  const shareMessage = `I scored ${score}/${totalQuestions} (${percentage}%) on the "${stripHtmlTags(quizTitle)}" quiz!`;
  
  // Get the quiz ID from the URL hash
  const quizId = window.location.hash.replace('#', '') || '';
  
  // Submit the score to the backend and fetch stats
  useEffect(() => {
    if (!quizData || !quizId) return;
    
    // Get topic from URL or use default
    const topic = getTopicFromUrl();
    
    // Submit score to backend
    const submitScore = async () => {
      if (scoreSaved) return;
      
      const success = await saveQuizScore(
        quizId,
        score,
        totalQuestions,
        topic,
        quizTitle
      );
      
      if (success) {
        setScoreSaved(true);
      }
    };
    
    // Fetch quiz stats
    const getStats = async () => {
      setLoadingStats(true);
      const stats = await fetchQuizStats(quizId);
      if (stats) {
        setQuizStats(stats);
      }
      setLoadingStats(false);
    };
    
    submitScore();
    getStats();
  }, [quizId, quizData, score, totalQuestions, quizTitle, scoreSaved]);
  
  // Generate cheat sheet
  const generateCheatSheet = () => {
    if (!quizData) return shareMessage;
    
    let cheatSheet = `CHEAT SHEET FOR: "${quizData.title}"\n\n`;
    
    quizData.questions.forEach((question, index) => {
      const correctAnswer = question.options[question.answer];
      
      cheatSheet += `Question ${index + 1}: ${stripHtmlTags(question.question)}\n`;
      cheatSheet += `Answer: ${stripHtmlTags(correctAnswer)}\n\n`;
    });
    
    return cheatSheet;
  };
  
  // Handle copy to clipboard
  const copyToClipboard = () => {
    navigator.clipboard.writeText(generateCheatSheet()).then(() => {
      setShowCopiedMessage(true);
      setTimeout(() => setShowCopiedMessage(false), 2000);
    });
  };
  
  return (
    <div className="quiz-result">
      <h2>Quiz Completed!</h2>
      
      {quizData && quizData.title && (
        <h3 className="quiz-title" dangerouslySetInnerHTML={renderHTML(quizData.title)} />
      )}
      
      <div className="score-container">
        <div className="score">{score} / {totalQuestions}</div>
        <div className="percentage">{percentage}%</div>
      </div>
      
      <div className="feedback">
        {percentage >= 80 ? (
          <p dangerouslySetInnerHTML={renderHTML("Excellent! You've <strong>mastered</strong> this quiz!")} />
        ) : percentage >= 60 ? (
          <p dangerouslySetInnerHTML={renderHTML("Good job! You're on the <strong>right track</strong>.")} />
        ) : percentage >= 40 ? (
          <p dangerouslySetInnerHTML={renderHTML("Not bad, but there's <strong>room for improvement</strong>.")} />
        ) : (
          <p dangerouslySetInnerHTML={renderHTML("Keep <strong>practicing</strong>! You'll get better.")} />
        )}
      </div>
      
      {/* Quiz Statistics */}
      {quizStats && (
        <div className="quiz-stats">
          <h3>How You Compare</h3>
          {loadingStats ? (
            <p>Loading statistics...</p>
          ) : (
            <>
              <div className="stats-grid">
                <div className="stat-item">
                  <div className="stat-value">{quizStats.totalAttempts}</div>
                  <div className="stat-label">Total Attempts</div>
                </div>
                <div className="stat-item">
                  <div className="stat-value">{quizStats.averageScore}%</div>
                  <div className="stat-label">Average Score</div>
                </div>
                <div className="stat-item">
                  <div className="stat-value">{quizStats.highScore}%</div>
                  <div className="stat-label">High Score</div>
                </div>
              </div>
              
              {quizStats.totalAttempts > 10 && (
                <div className="score-distribution">
                  <h4>Score Distribution</h4>
                  <div className="distribution-bar">
                    {Object.entries(quizStats.distribution).map(([range, count]) => {
                      const percentage = (count / quizStats.totalAttempts) * 100;
                      return (
                        <div 
                          key={range} 
                          className="distribution-segment"
                          style={{
                            width: `${percentage}%`,
                            backgroundColor: 
                              range === '81-100' ? '#4caf50' :
                              range === '61-80' ? '#8bc34a' :
                              range === '41-60' ? '#ffeb3b' :
                              range === '21-40' ? '#ff9800' :
                                                '#f44336'
                          }}
                          title={`${range}%: ${count} attempts (${Math.round(percentage)}%)`}
                        />
                      );
                    })}
                  </div>
                  <div className="distribution-labels">
                    <span>0%</span>
                    <span>100%</span>
                  </div>
                </div>
              )}
            </>
          )}
        </div>
      )}
      
      <div className="share-container">
        <div className="share-buttons">
          <button 
            className="copy-button"
            onClick={copyToClipboard}
            aria-label="Copy cheat sheet to clipboard"
            title="Copy cheat sheet to clipboard"
          >
            <span className="copy-icon">📋</span>
            <span className="copy-text">Copy Cheat Sheet to Clipboard</span>
            {showCopiedMessage && <span className="copied-tooltip">Copied!</span>}
          </button>
        </div>
      </div>
      
      <div className="result-buttons">
        <button className="restart-button" onClick={onRestart}>
          Try Again
        </button>
        {onChooseNewQuiz && (
          <button className="new-quiz-button" onClick={onChooseNewQuiz}>
            Choose New Quiz
          </button>
        )}
        {onNextQuiz && (
          <button className="next-quiz-button" onClick={onNextQuiz}>
            {nextQuizTitle ? `Next Quiz: ${nextQuizTitle}` : 'Next Quiz'}
          </button>
        )}
      </div>
    </div>
  );
};

export default QuizResult;
