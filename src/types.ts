export interface Question {
  id: number;
  question: string;
  options: string[];
  answer: number; // index of the correct option
}

export interface QuizData {
  title: string;
  description: string;
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
}
