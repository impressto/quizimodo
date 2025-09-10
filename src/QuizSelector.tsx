import { useState, useEffect } from 'react';
import './QuizSelector.css';
import type { QuizMetadata } from './types';
import { getQuizzesBaseUrl, DEFAULT_TOPIC } from './config';

interface QuizSelectorProps {
  onSelectQuiz: (quizId: string) => void;
  topic?: string;
}

const QuizSelector = ({ onSelectQuiz, topic = DEFAULT_TOPIC }: QuizSelectorProps) => {
  const [availableQuizzes, setAvailableQuizzes] = useState<QuizMetadata[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchQuizzes = async () => {
      try {
        const baseUrl = getQuizzesBaseUrl(topic);
        console.log('Fetching quizzes from URL:', `${baseUrl}/quizzes-meta.json`);
        
        // First, fetch the quizzes-meta.json that lists all available quizzes
        const metaResponse = await fetch(`${baseUrl}/quizzes-meta.json`);
        
        if (!metaResponse.ok) {
          console.error('Failed to fetch quiz list:', metaResponse.status, metaResponse.statusText);
          throw new Error('Failed to fetch quiz list');
        }
        
        const metaData = await metaResponse.json();
        
        // Now, fetch each quiz to get its title and description
        const quizPromises = metaData.quizzes.map(async (quiz: { id: string, file: string }) => {
          try {
            const response = await fetch(`${baseUrl}/${quiz.file}`);
            
            if (!response.ok) {
              throw new Error(`Failed to load quiz: ${quiz.id}`);
            }
            
            const quizData = await response.json();
            
            return {
              id: quiz.id,
              title: quizData.title || `Quiz ${quiz.id}`,
              description: quizData.description || 'No description available.',
              questionCount: quizData.questions?.length || 0,
              time: quizData.time
            };
          } catch (error) {
            console.error(`Error loading quiz ${quiz.id}:`, error);
            return {
              id: quiz.id,
              title: `Quiz ${quiz.id}`,
              description: 'Error loading quiz details.',
              questionCount: 0,
              error: true,
              time: undefined
            };
          }
        });
        
        const quizzes = await Promise.all(quizPromises);
        const validQuizzes = quizzes.filter(quiz => !quiz.error);
        setAvailableQuizzes(validQuizzes);
        
        // If there's only one quiz, automatically select it
        if (validQuizzes.length === 1) {
          onSelectQuiz(validQuizzes[0].id);
        }
        
        setIsLoading(false);
      } catch (err) {
        console.error('Error loading quiz list:', err);
        setError('Failed to load quiz list. Please try again later.');
        setIsLoading(false);
      }
    };

    fetchQuizzes();
  }, [topic, onSelectQuiz]);

  if (isLoading) {
    return <div className="loading">Loading available quizzes...</div>;
  }

  if (error) {
    return <div className="error">{error}</div>;
  }
  
  // If there's only one quiz, it will be auto-selected in the useEffect,
  // but we still need to render something until loading completes
  if (availableQuizzes.length === 1) {
    return <div className="loading">Loading quiz...</div>;
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
                {quiz.time && <span className="quiz-time">⏱️ {quiz.time}</span>}
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
