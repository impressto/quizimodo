import { useState } from 'react';
import type { Question } from './types';
import './QuizQuestion.css';

interface QuizQuestionProps {
  question: Question;
  onAnswer: (selectedIndex: number) => void;
  correctStreak?: number; // Optional streak count
}

const QuizQuestion = ({ question, onAnswer, correctStreak = 0 }: QuizQuestionProps) => {
  const [selectedOption, setSelectedOption] = useState<number | null>(null);
  const [showCorrectAnswer, setShowCorrectAnswer] = useState<boolean>(false);

  const handleOptionClick = (index: number) => {
    setSelectedOption(index);
    
    // If the answer is incorrect, show the correct answer
    if (index !== question.answer) {
      setShowCorrectAnswer(true);
    }
    
    setTimeout(() => {
      onAnswer(index);
      setSelectedOption(null);
      setShowCorrectAnswer(false);
    }, 1500); // Increased timeout to give users time to see the correct answer
  };

  return (
    <div className="quiz-question">
      {correctStreak > 0 && (
        <div className="streak-counter">
          <span className="streak-text">Streak: </span>
          <span className="streak-count">{correctStreak}</span>
          {correctStreak >= 3 && <span className="streak-fire">🔥</span>}
        </div>
      )}
      <h2>{question.question}</h2>
      <div className="options">
        {question.options.map((option, index) => (
          <button
            key={index}
            className={`option 
              ${selectedOption === index ? 'selected' : ''} 
              ${selectedOption === index && selectedOption === question.answer ? 'correct' : ''} 
              ${selectedOption === index && selectedOption !== question.answer ? 'incorrect' : ''}
              ${showCorrectAnswer && index === question.answer ? 'show-correct' : ''}
            `}
            onClick={() => handleOptionClick(index)}
            disabled={selectedOption !== null}
          >
            {option}
            {showCorrectAnswer && index === question.answer && (
              <span className="correct-answer-indicator">✓ Correct Answer</span>
            )}
          </button>
        ))}
      </div>
    </div>
  );
};

export default QuizQuestion;
