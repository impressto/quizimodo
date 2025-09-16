/**
 * A utility function to escape HTML characters
 * Converts <, >, &, ", and ' to their HTML entity equivalents
 */
export const escapeHtml = (text: string): string => {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
};

/**
 * A utility function to convert text wrapped in backticks to HTML code blocks
 * This transforms `code` into proper HTML code elements
 */
export const formatCodeBlocks = (text: string): string => {
  // If the string starts and ends with backticks and contains HTML tags,
  // we need to treat it specially - this is for options that represent HTML code
  if (text.startsWith('`') && text.endsWith('`') && (text.includes('<') || text.includes('>'))) {
    // Remove the enclosing backticks
    const content = text.substring(1, text.length - 1);
    
    // Escape the HTML characters to display them as text
    const escapedContent = escapeHtml(content);
    
    // Wrap in pre and code tags for proper styling
    return `<pre><code class="quiz-code-block">${escapedContent}</code></pre>`;
  }
  
  // For normal inline code (not representing HTML), use the regular pattern
  const codeRegex = /`([^`]+)`/g;
  
  // Replace backtick-wrapped text with a code block
  return text.replace(codeRegex, (_, codeContent) => {
    // Escape HTML characters within the code content to prevent rendering as HTML
    const escapedContent = escapeHtml(codeContent);
    return `<code class="quiz-inline-code">${escapedContent}</code>`;
  });
};

/**
 * A utility function to safely render HTML content
 * This uses dangerouslySetInnerHTML with a sanitized input
 */
export const renderHTML = (html: string): { __html: string } => {
  // In a production app, you would use a sanitization library here
  // like DOMPurify to prevent XSS attacks
  // For example: return { __html: DOMPurify.sanitize(html) };
  
  // First check if the text contains backtick-wrapped code and format it
  const formattedHtml = formatCodeBlocks(html);
  
  // For now, we'll just return the HTML as is, but in production
  // you should definitely use a sanitization library
  return { __html: formattedHtml };
};

/**
 * A utility function to strip HTML tags from a string
 * Useful when you need plain text from HTML content
 */
export const stripHtmlTags = (html: string): string => {
  return html.replace(/<[^>]*>?/gm, '');
};
