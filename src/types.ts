export interface Question {
  id: number;
  question: string;
  example?: string; // Optional HTML example to show between question and options
  options: string[];
  answer: number; // index of the correct option
  explanation?: string; // Optional explanation to show after answering
  exampleExplanation?: string; // Optional HTML example for the explanation
  headerimage?: string; // Optional question-specific header image
}

export interface QuizData {
  title: string;
  description: string;
  headerimage?: string; // Optional header image for quiz theme
  headerimageWidth?: number; // Optional width for header image (in px)
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
