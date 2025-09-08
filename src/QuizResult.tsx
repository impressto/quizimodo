import './QuizResult.css';

interface QuizResultProps {
  score: number;
  totalQuestions: number;
  onRestart: () => void;
  onChooseNewQuiz?: () => void; // Optional callback to choose a new quiz
}

const QuizResult = ({ score, totalQuestions, onRestart, onChooseNewQuiz }: QuizResultProps) => {
  const percentage = Math.round((score / totalQuestions) * 100);
  
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
      
      <div className="result-buttons">
        <button className="restart-button" onClick={onRestart}>
          Try Again
        </button>
        {onChooseNewQuiz && (
          <button className="new-quiz-button" onClick={onChooseNewQuiz}>
            Choose New Quiz
          </button>
        )}
      </div>
    </div>
  );
};

export default QuizResult;
