import { useState, useEffect } from 'react';
import './App.css';
import QuizQuestion from './QuizQuestion';
import QuizResult from './QuizResult';
import QuizSelector from './QuizSelector';
import { loadQuiz } from './quizService';
import type { QuizState } from './types';
import Celebration from './Celebration';
import { DEFAULT_TOPIC } from './config';

interface AppProps {
  topic?: string;
}

function App({ topic = DEFAULT_TOPIC }: AppProps) {
  const [quizState, setQuizState] = useState<QuizState>({
    quizData: null,
    currentQuestionIndex: 0,
    score: 0,
    quizCompleted: false,
    isLoading: false,
    error: null
  });

  const [selectedQuizId, setSelectedQuizId] = useState<string | null>(null);
  const [correctStreak, setCorrectStreak] = useState<number>(0);
  const [showCelebration, setShowCelebration] = useState<boolean>(false);

  useEffect(() => {
    if (!selectedQuizId) return;
    
    const fetchQuiz = async () => {
      setQuizState(prev => ({
        ...prev,
        isLoading: true,
        error: null
      }));
      
      try {
        const data = await loadQuiz(selectedQuizId, topic);
        setQuizState(prev => ({
          ...prev,
          quizData: data,
          isLoading: false,
          currentQuestionIndex: 0,
          score: 0,
          quizCompleted: false
        }));
      } catch (error) {
        setQuizState(prev => ({
          ...prev,
          isLoading: false,
          error: 'Failed to load quiz. Please try again.'
        }));
      }
    };
    
    fetchQuiz();
  }, [selectedQuizId, topic]);

  const handleSelectQuiz = (quizId: string) => {
    setSelectedQuizId(quizId);
  };

  const handleAnswer = (selectedIndex: number) => {
    if (!quizState.quizData) return;
    
    const currentQuestion = quizState.quizData.questions[quizState.currentQuestionIndex];
    const isCorrect = selectedIndex === currentQuestion.answer;
    
    if (isCorrect) {
      // Update streak count
      const newStreak = correctStreak + 1;
      setCorrectStreak(newStreak);
      
      // Show celebration after 5 correct answers in a row
      if (newStreak === 5) {
        setShowCelebration(true);
        // Hide celebration after 2 seconds
        setTimeout(() => {
          setShowCelebration(false);
        }, 2000);
      }
    } else {
      // Reset streak on wrong answer
      setCorrectStreak(0);
    }
    
    setQuizState(prev => ({
      ...prev,
      score: isCorrect ? prev.score + 1 : prev.score,
      currentQuestionIndex: prev.currentQuestionIndex + 1,
      quizCompleted: prev.currentQuestionIndex === prev.quizData!.questions.length - 1
    }));
  };

  const restartQuiz = () => {
    // Reset streak when restarting the quiz
    setCorrectStreak(0);
    setShowCelebration(false);
    
    setQuizState(prev => ({
      ...prev,
      currentQuestionIndex: 0,
      score: 0,
      quizCompleted: false
    }));
  };
  
  const chooseNewQuiz = () => {
    setSelectedQuizId(null);
    setCorrectStreak(0);
    setShowCelebration(false);
  };

  return (
    <div className="app">
      {showCelebration && <Celebration />}
      
      <header>
        {quizState.quizData && !quizState.quizCompleted && (
          <div className="quiz-header">
            <h2>{quizState.quizData.title}</h2>
            <div className="quiz-info">
              {quizState.quizData.time && (
                <div className="quiz-time">
                  <span className="time-icon">⏱️</span>
                  <span>Estimated time: {quizState.quizData.time}</span>
                </div>
              )}
              {selectedQuizId && !quizState.isLoading && !quizState.error && (
                <button onClick={chooseNewQuiz} className="choose-new-button">
                  Choose a different quiz
                </button>
              )}
            </div>
          </div>
        )}
      </header>
      
      <main>
        {!selectedQuizId ? (
          <QuizSelector onSelectQuiz={handleSelectQuiz} topic={topic} />
        ) : quizState.isLoading ? (
          <div className="loading">Loading quiz...</div>
        ) : quizState.error ? (
          <div className="error">
            {quizState.error}
            <button onClick={chooseNewQuiz} className="back-button">Go back to quiz selection</button>
          </div>
        ) : quizState.quizCompleted && quizState.quizData ? (
          <QuizResult 
            score={quizState.score} 
            totalQuestions={quizState.quizData.questions.length}
            onRestart={restartQuiz}
            onChooseNewQuiz={chooseNewQuiz}
          />
        ) : quizState.quizData ? (
          <QuizQuestion 
            question={quizState.quizData.questions[quizState.currentQuestionIndex]}
            onAnswer={handleAnswer}
            correctStreak={correctStreak}
          />
        ) : null}
      </main>

      <footer>
        {selectedQuizId && !quizState.isLoading && !quizState.error && !quizState.quizCompleted && quizState.quizData && (
          <div className="progress">
            Question {quizState.currentQuestionIndex + 1} of {quizState.quizData.questions.length}
          </div>
        )}
      </footer>
    </div>
  );
}

export default App;
