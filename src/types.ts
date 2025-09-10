export interface Question {
  id: number;
  question: string;
  options: string[];
  answer: number; // index of the correct option
  explanation?: string; // Optional explanation to show after answering
}

export interface QuizData {
  title: string;
  description: string;
  headerimage?: string; // Optional header image for quiz theme
  time?: string; // Estimated time to complete the quiz
  questions: Question[];
}

export interface QuizState {
  quizData: QuizData | null;
  currentQuestionIndex: number;
  score: number;
  quizCompleted: boolean;
  isLoading: boolean;
  error: string | null;
}

export interface QuizMetadata {
  id: string;
  title: string;
  description: string;
  questionCount: number;
  time?: string; // Estimated time to complete the quiz
}
