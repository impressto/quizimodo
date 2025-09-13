import { useState, useEffect, useCallback } from 'react';
import './App.css';
import QuizQuestion from './QuizQuestion';
import QuizResult from './QuizResult';
import QuizSelector from './QuizSelector';
import { loadQuiz } from './quizService';
import type { QuizState } from './types';
import Celebration from './Celebration';
import Modal from './Modal';
import { DEFAULT_TOPIC, getQuizzesBaseUrl } from './config';

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
  const [celebrationType, setCelebrationType] = useState<'basic' | 'amazing' | 'mindblowing'>('basic');
  const [showLeaveQuizModal, setShowLeaveQuizModal] = useState<boolean>(false);
  const [availableQuizCount, setAvailableQuizCount] = useState<number>(0);

  // Fetch the number of available quizzes from the metadata
  useEffect(() => {
    const fetchQuizMeta = async () => {
      try {
        const baseUrl = getQuizzesBaseUrl(topic);
        const metaResponse = await fetch(`${baseUrl}/quizzes-meta.json`);
        if (metaResponse.ok) {
          const metaData = await metaResponse.json();
          const quizIds = metaData.quizzes ? metaData.quizzes.map((q: any) => q.id) : [];
          setAvailableQuizCount(quizIds.length);
          // Hash-based selection
          const hash = window.location.hash.replace('#', '');
          if (hash && quizIds.includes(hash)) {
            setSelectedQuizId(hash);
          }
        } else {
          setAvailableQuizCount(0);
        }
      } catch (error) {
        setAvailableQuizCount(0);
      }
    };
    fetchQuizMeta();
  }, [topic]);

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

  const handleSelectQuiz = useCallback((quizId: string) => {
  setSelectedQuizId(quizId);
  window.location.hash = quizId;
  }, []);

  const CELEBRATION_TIMEOUT = Number(import.meta.env.VITE_CELEBRATION_TIMEOUT) || 3000;
  const handleAnswer = (selectedIndex: number) => {
    if (!quizState.quizData) return;

    const currentQuestion = quizState.quizData.questions[quizState.currentQuestionIndex];
    const isCorrect = selectedIndex === currentQuestion.answer;

    if (isCorrect) {
      // Update streak count
      const newStreak = correctStreak + 1;
      setCorrectStreak(newStreak);

      // Show different celebrations based on streak milestones
      if (newStreak === 5) {
        setCelebrationType('basic');
        setShowCelebration(true);
        setTimeout(() => {
          setShowCelebration(false);
        }, CELEBRATION_TIMEOUT);
      } else if (newStreak === 10) {
        setCelebrationType('amazing');
        setShowCelebration(true);
        setTimeout(() => {
          setShowCelebration(false);
        }, CELEBRATION_TIMEOUT);
      } else if (newStreak === 20) {
        setCelebrationType('mindblowing');
        setShowCelebration(true);
        setTimeout(() => {
          setShowCelebration(false);
        }, CELEBRATION_TIMEOUT);
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
    setCelebrationType('basic');
    
    setQuizState(prev => ({
      ...prev,
      currentQuestionIndex: 0,
      score: 0,
      quizCompleted: false
    }));
  };
  
  const resetQuizState = () => {
    // Reset all quiz-related state
    setSelectedQuizId(null);
    setCorrectStreak(0);
    setShowCelebration(false);
    setCelebrationType('basic');
    setQuizState({
      quizData: null,
      currentQuestionIndex: 0,
      score: 0,
      quizCompleted: false,
      isLoading: false,
      error: null
    });
    // Remove hash from URL
    if (window.location.hash) {
      history.replaceState(null, '', window.location.pathname + window.location.search);
    }
  };

  const confirmLeaveQuiz = () => {
    resetQuizState();
    setShowLeaveQuizModal(false); // Close the modal
  };

  const cancelLeaveQuiz = () => {
    setShowLeaveQuizModal(false); // Just close the modal
  };
  
  const chooseNewQuiz = () => {
    // If the quiz is in progress (not completed and not on the first question with no score),
    // show a confirmation dialog
    const quizInProgress = !quizState.quizCompleted && 
                          (quizState.currentQuestionIndex > 0 || quizState.score > 0);
    
    if (quizInProgress) {
      // Show custom modal instead of window.confirm
      setShowLeaveQuizModal(true);
      return;
    }
    
    // If quiz is not in progress, directly proceed with resetting the quiz state
    resetQuizState();
  };

  // Use headerimage from quizData if present, prepending base path from .env if needed
  let quizImagePath: string | null = null;
  if (quizState.quizData && quizState.quizData.headerimage) {
    const img = quizState.quizData.headerimage;
    // If path is relative, prepend base URL from .env
    if (img.startsWith('http://') || img.startsWith('https://')) {
      quizImagePath = img;
    } else {
      const baseUrl = import.meta.env.VITE_IMAGE_BASE_URL || '';
      quizImagePath = baseUrl.replace(/\/$/, '') + img;
    }
  }

  // Show celebration if all answers are correct when quiz is completed
  const allCorrect = quizState.quizCompleted && quizState.quizData && quizState.score === quizState.quizData.questions.length;
  const [allCorrectCelebration, setAllCorrectCelebration] = useState(false);

  // Show celebration for CELEBRATION_TIMEOUT if all answers are correct
  useEffect(() => {
    if (allCorrect) {
      setAllCorrectCelebration(true);
      const timer = setTimeout(() => setAllCorrectCelebration(false), CELEBRATION_TIMEOUT);
      return () => clearTimeout(timer);
    }
  }, [allCorrect, CELEBRATION_TIMEOUT]);

  const shouldShowCelebration = showCelebration || allCorrectCelebration;
  // Load celebration gifs config
  const [celebrationGifs, setCelebrationGifs] = useState<{ [key: string]: string }>({});
  useEffect(() => {
    const fetchConfig = async () => {
      if (!topic) return;
      // Use VITE_IMAGE_BASE_URL for config path
      let configPath = '';
      const baseUrl = import.meta.env.VITE_IMAGE_BASE_URL || '';
      if (import.meta.env.MODE === 'development') {
        configPath = `/quizzes/${topic}/config.json`;
      } else {
        configPath = baseUrl + `/${topic}/config.json`;
      }
      try {
        const res = await fetch(configPath);
        if (res.ok) {
          const config = await res.json();
          setCelebrationGifs(config.celebrationGifs || {});
          // Preload gifs
          const gifs = config.celebrationGifs || {};
          Object.values(gifs).forEach(filename => {
            const img = new window.Image();
            img.src = baseUrl + `/${topic}/${filename}`;
          });
        }
      } catch (e) {
        // fallback: no config
        setCelebrationGifs({});
      }
    };
    fetchConfig();
  }, [topic]);

  // Select appropriate gif from config
  let congratsImage: string | undefined = undefined;
  const baseGifUrl = (import.meta.env.VITE_IMAGE_BASE_URL || '');
  if (allCorrect && celebrationGifs['all']) {
    congratsImage = baseGifUrl + `/${topic}/${celebrationGifs['all']}`;
  } else if (celebrationType === 'mindblowing' && celebrationGifs['20']) {
    congratsImage = baseGifUrl + `/${topic}/${celebrationGifs['20']}`;
  } else if (celebrationType === 'amazing' && celebrationGifs['10']) {
    congratsImage = baseGifUrl + `/${topic}/${celebrationGifs['10']}`;
  } else if (celebrationType === 'basic' && celebrationGifs['5']) {
    congratsImage = baseGifUrl + `/${topic}/${celebrationGifs['5']}`;
  }

  if (shouldShowCelebration) {
    // Debug log for congratsImage path
    // eslint-disable-next-line no-console
    console.log('Celebration gif path:', congratsImage);
  }

  return (
    <div className="app">
      {/* Custom Modal for quiz leaving confirmation */}
      <Modal 
        isOpen={showLeaveQuizModal}
        title="Leave Quiz?"
        message="Are you sure you want to leave this quiz? Your progress will be lost."
        onConfirm={confirmLeaveQuiz}
        onCancel={cancelLeaveQuiz}
      />

      {shouldShowCelebration && (
        <Celebration streakLevel={celebrationType} congratsImage={congratsImage} />
      )}

      <header>
        {quizState.quizData && !quizState.quizCompleted && (
          <div className="quiz-header">
            {/* Show quiz theme image if available */}
            {quizImagePath && (
              <img
                src={quizImagePath}
                alt="Quiz Theme"
                className="quiz-theme-image"
                style={{ 
                  maxWidth: quizState.quizData?.headerimageWidth ? `${quizState.quizData.headerimageWidth}px` : '200px', 
                  marginBottom: '1rem' 
                }}
                onError={e => (e.currentTarget.style.display = 'none')}
              />
            )}
            <h2>{quizState.quizData.title}</h2>
            <div className="quiz-info">
              {quizState.quizData.time && (
                <div className="quiz-time">
                  <span className="time-icon">⏱️</span>
                  <span>Estimated time: {quizState.quizData.time}</span>
                </div>
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
            quizTitle={quizState.quizData.title}
            quizData={quizState.quizData}
            onRestart={restartQuiz}
            onChooseNewQuiz={availableQuizCount > 1 ? chooseNewQuiz : undefined}
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
        {selectedQuizId && !quizState.isLoading && !quizState.error && availableQuizCount > 1 && (
          <div className="footer-actions">
            <button onClick={chooseNewQuiz} className="choose-new-button">
              Choose a different quiz
            </button>
          </div>
        )}
        <div className="github-link">
          <a href="https://github.com/impressto/quizzimodo" target="_blank" rel="noopener noreferrer">
            Want quizzes like this on your own website? Grab the code here.
          </a>
        </div>
      </footer>
    </div>
  );
}

export default App;
