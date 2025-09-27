// Configuration file to handle environment-specific settings

// Default topic if none is specified
export const DEFAULT_TOPIC = 'default';

// Function to determine if we're running in static mode
export const isStaticMode = (): boolean => {
  return import.meta.env.VITE_STATIC_MODE === 'true';
};

// Function to determine if we're running in production
export const isProduction = (): boolean => {
  return window.location.hostname !== 'localhost' && 
         !window.location.hostname.includes('127.0.0.1') &&
         !window.location.hostname.includes('0.0.0.0');
};

// Read topic from data-topic attribute, URL query parameters, or infer from path
export const getTopicFromUrl = (): string => {
  // First check if data-topic is set on the root element
  const rootElement = document.getElementById('root');
  const dataTopic = rootElement?.getAttribute('data-topic');
  if (dataTopic) {
    return dataTopic;
  }
  
  // Next check URL query parameter
  const urlParams = new URLSearchParams(window.location.search);
  const topicParam = urlParams.get('topic');
  if (topicParam) {
    return topicParam;
  }
  
  // Try to infer from URL path
  const pathname = window.location.pathname;
  
  // Check if URL contains /quizzes/[topic]/
  const quizzesMatch = pathname.match(/\/quizzes\/([^\/]+)\//);
  if (quizzesMatch && quizzesMatch[1]) {
    return quizzesMatch[1];
  }
  
  // Return default if no topic can be inferred
  return DEFAULT_TOPIC;
};

// Function to get the base URL for quiz assets
export const getQuizzesBaseUrl = (topic?: string): string => {
  const quizTopic = topic || getTopicFromUrl();
  
  if (isProduction()) {
    // Use VITE_IMAGE_BASE_URL from environment variables if available
    const baseUrl = import.meta.env.VITE_IMAGE_BASE_URL || 'https://impressto.ca/quizzes/public/quizzes';
    return `${baseUrl}/${quizTopic}`;
  }
  
  // In development, always include the topic in the path
  // Because our directory structure is /public/quizzes/react/ or /public/quizzes/[topic]/
  return `/quizzes/${quizTopic}`;
};
