import type { QuizData } from './types';
import { getQuizzesBaseUrl } from './config';

// Helper function to get a list of all available quizzes
export async function fetchAvailableQuizzes(topic?: string): Promise<string[]> {
  try {
    const baseUrl = getQuizzesBaseUrl(topic);
    const metaResponse = await fetch(`${baseUrl}/quizzes-meta.json`);
    
    if (!metaResponse.ok) {
      throw new Error('Failed to fetch quiz list');
    }
    
    const metaData = await metaResponse.json();
    return metaData.quizzes.map((quiz: { id: string }) => quiz.id);
  } catch (error) {
    console.error('Error fetching available quizzes:', error);
    return [];
  }
}

// Helper function to load a quiz by ID
export async function loadQuiz(quizId: string, topic?: string): Promise<QuizData> {
  try {
    const baseUrl = getQuizzesBaseUrl(topic);
    
    // First, get the file name from meta data
    const metaResponse = await fetch(`${baseUrl}/quizzes-meta.json`);
    
    if (!metaResponse.ok) {
      throw new Error(`Failed to load quiz metadata: ${metaResponse.statusText}`);
    }
    
    const metaData = await metaResponse.json();
    const quizMeta = metaData.quizzes.find((quiz: { id: string }) => quiz.id === quizId);
    
    if (!quizMeta) {
      throw new Error(`Quiz with ID ${quizId} not found`);
    }
    
    // Now load the actual quiz file
    const response = await fetch(`${baseUrl}/${quizMeta.file}`);
    
    if (!response.ok) {
      throw new Error(`Failed to load quiz: ${response.statusText}`);
    }
    
    const quizData = await response.json();
    return quizData as QuizData;
  } catch (error) {
    console.error('Error loading quiz:', error);
    throw error;
  }
}
