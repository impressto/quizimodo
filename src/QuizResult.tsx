import { useState } from 'react';
import './QuizResult.css';
import type { QuizData } from './types';

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
  
  // Generate share message
  const shareMessage = `I scored ${score}/${totalQuestions} (${percentage}%) on the "${quizTitle}" quiz!`;
  
  // Generate cheat sheet
  const generateCheatSheet = () => {
    if (!quizData) return shareMessage;
    
    let cheatSheet = `CHEAT SHEET FOR: "${quizData.title}"\n\n`;
    
    quizData.questions.forEach((question, index) => {
      const correctAnswer = question.options[question.answer];
      cheatSheet += `Question ${index + 1}: ${question.question}\n`;
      cheatSheet += `Answer: ${correctAnswer}\n\n`;
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
      <div className="score-container">
        <div className="score">{score} / {totalQuestions}</div>
        <div className="percentage">{percentage}%</div>
      </div>
      
      <div className="feedback">
        {percentage >= 80 ? (
          <p>Excellent! You've mastered this quiz!</p>
        ) : percentage >= 60 ? (
          <p>Good job! You're on the right track.</p>
        ) : percentage >= 40 ? (
          <p>Not bad, but there's room for improvement.</p>
        ) : (
          <p>Keep practicing! You'll get better.</p>
        )}
      </div>
      
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
