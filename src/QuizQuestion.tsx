import { useState, useMemo } from 'react';
import type { Question } from './types';
import './QuizQuestion.css';
import './CodeStyles.css';
import './ExampleStyles.css';
import { renderHTML } from './utils';

// Shuffle function using Fisher-Yates algorithm with index tracking
function shuffleArray<T>(array: T[]): { shuffled: T[], originalIndices: number[] } {
  const shuffled = [...array];
  
  // Create an array of original indices [0, 1, 2, ...]
  const originalIndices = Array.from({ length: array.length }, (_, i) => i);
  
  for (let i = shuffled.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    
    // Swap both the items and their original indices
    [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    [originalIndices[i], originalIndices[j]] = [originalIndices[j], originalIndices[i]];
  }
  
  return { shuffled, originalIndices };
}

interface QuizQuestionProps {
  question: Question;
  onAnswer: (selectedIndex: number) => void;
  correctStreak?: number; // Optional streak count
}

const QuizQuestion = ({ question, onAnswer, correctStreak = 0 }: QuizQuestionProps) => {
  const [selectedOption, setSelectedOption] = useState<number | null>(null);
  const [showCorrectAnswer, setShowCorrectAnswer] = useState<boolean>(false);
  const [showExplanation, setShowExplanation] = useState<boolean>(false);
  const [answered, setAnswered] = useState<boolean>(false);
  
  // Shuffle options and keep track of their original positions
  const { shuffled: shuffledOptions, originalIndices } = useMemo(() => 
    shuffleArray(question.options), 
    [question.id] // Only re-shuffle when the question changes
  );
  
  // Find the correct answer's new index in the shuffled array
  const shuffledAnswerIndex = useMemo(() => {
    return originalIndices.findIndex(index => index === question.answer);
  }, [originalIndices, question.answer]);
  
  const handleOptionClick = (index: number) => {
    setSelectedOption(index);
    setAnswered(true);
    
    // If the answer is incorrect, show the correct answer and explanation
    if (index !== shuffledAnswerIndex) {
      setShowCorrectAnswer(true);
      
      // Only show explanation for incorrect answers
      if (question.explanation) {
        setShowExplanation(true);
      }
    }
  };
  
  const handleContinue = () => {
    if (selectedOption === null) return;
    
    // Get the original index of the selected option to pass back to the parent component
    const selectedOriginalIndex = originalIndices[selectedOption];
    
    // Pass the answer to the parent component
    onAnswer(selectedOriginalIndex);
    
    // Reset the component state
    setSelectedOption(null);
    setShowCorrectAnswer(false);
    setShowExplanation(false);
    setAnswered(false);
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
      <h2 dangerouslySetInnerHTML={renderHTML(question.question)} />
      
      {question.example && (
        <div className="question-example" dangerouslySetInnerHTML={renderHTML(question.example)} />
      )}
      
      <div className="options">
        {shuffledOptions.map((option, index) => (
          <button
            key={index}
            className={`option 
              ${selectedOption === index ? 'selected' : ''} 
              ${selectedOption === index && selectedOption === shuffledAnswerIndex ? 'correct' : ''} 
              ${selectedOption === index && selectedOption !== shuffledAnswerIndex ? 'incorrect' : ''}
              ${showCorrectAnswer && index === shuffledAnswerIndex ? 'show-correct' : ''}
            `}
            onClick={() => handleOptionClick(index)}
            disabled={selectedOption !== null}
          >
            <span dangerouslySetInnerHTML={renderHTML(option)} />
            {showCorrectAnswer && index === shuffledAnswerIndex && (
              <span className="correct-answer-indicator">✓ Correct Answer</span>
            )}
          </button>
        ))}
      </div>
      {showExplanation && question.explanation && (
        <div className="explanation-container">
          <h3>Explanation:</h3>
          <div dangerouslySetInnerHTML={renderHTML(question.explanation)} />
          
          {question.exampleExplanation && (
            <div className="explanation-example" dangerouslySetInnerHTML={renderHTML(question.exampleExplanation)} />
          )}
        </div>
      )}
      {answered && (
        <div className="continue-button-container">
          <button 
            className="continue-button"
            onClick={handleContinue}
          >
            Continue
          </button>
        </div>
      )}
    </div>
  );
};

export default QuizQuestion;
