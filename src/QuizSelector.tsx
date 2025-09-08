import { useState, useEffect } from 'react';
import './QuizSelector.css';
import type { QuizMetadata } from './types';

interface QuizSelectorProps {
  onSelectQuiz: (quizId: string) => void;
}

const QuizSelector = ({ onSelectQuiz }: QuizSelectorProps) => {
  const [availableQuizzes, setAvailableQuizzes] = useState<QuizMetadata[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchQuizzes = async () => {
      try {
        // First, fetch the quizzes-meta.json that lists all available quizzes
        const metaResponse = await fetch('/quizzes/quizzes-meta.json');
        
        if (!metaResponse.ok) {
          throw new Error('Failed to fetch quiz list');
        }
        
        const metaData = await metaResponse.json();
        
        // Now, fetch each quiz to get its title and description
        const quizPromises = metaData.quizzes.map(async (quiz: { id: string, file: string }) => {
          try {
            const response = await fetch(`/quizzes/${quiz.file}`);
            
            if (!response.ok) {
              throw new Error(`Failed to load quiz: ${quiz.id}`);
            }
            
            const quizData = await response.json();
            
            return {
              id: quiz.id,
              title: quizData.title || `Quiz ${quiz.id}`,
              description: quizData.description || 'No description available.',
              questionCount: quizData.questions?.length || 0
            };
          } catch (error) {
            console.error(`Error loading quiz ${quiz.id}:`, error);
            return {
              id: quiz.id,
              title: `Quiz ${quiz.id}`,
              description: 'Error loading quiz details.',
              questionCount: 0,
              error: true
            };
          }
        });
        
        const quizzes = await Promise.all(quizPromises);
        setAvailableQuizzes(quizzes.filter(quiz => !quiz.error));
        setIsLoading(false);
      } catch (err) {
        console.error('Error loading quiz list:', err);
        setError('Failed to load quiz list. Please try again later.');
        setIsLoading(false);
      }
    };

    fetchQuizzes();
  }, []);

  if (isLoading) {
    return <div className="loading">Loading available quizzes...</div>;
  }

  if (error) {
    return <div className="error">{error}</div>;
  }

  return (
    <div className="quiz-selector">
      <h2>Choose a Quiz</h2>
      {availableQuizzes.length === 0 && !isLoading && !error ? (
        <div className="no-quizzes">No quizzes available.</div>
      ) : (
        <div className="quiz-list">
          {availableQuizzes.map((quiz) => (
            <div key={quiz.id} className="quiz-card" onClick={() => onSelectQuiz(quiz.id)}>
              <h3>{quiz.title}</h3>
              <p>{quiz.description}</p>
              <div className="quiz-info">
                <span>{quiz.questionCount} question{quiz.questionCount !== 1 ? 's' : ''}</span>
                <span className="quiz-id">{quiz.id}</span>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default QuizSelector;
