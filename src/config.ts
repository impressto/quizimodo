// Configuration file to handle environment-specific settings

// Default topic if none is specified
export const DEFAULT_TOPIC = 'react';

// Function to determine if we're running in production
export const isProduction = (): boolean => {
  return window.location.hostname !== 'localhost' && 
         !window.location.hostname.includes('127.0.0.1') &&
         !window.location.hostname.includes('0.0.0.0');
};

// Read topic from URL query parameters (e.g., ?topic=react)
export const getTopicFromUrl = (): string => {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get('topic') || DEFAULT_TOPIC;
};

// Function to get the base URL for quiz assets
export const getQuizzesBaseUrl = (topic?: string): string => {
  const quizTopic = topic || getTopicFromUrl();
  
  if (isProduction()) {
    return `https://impressto.ca/quizzes/public/quizzes/${quizTopic}`;
  }
  
  // In development, always include the topic in the path
  // Because our directory structure is /public/quizzes/react/ or /public/quizzes/[topic]/
  return `/quizzes/${quizTopic}`;
};
