# Quizimodo

[Live Demo](https://impressto.ca/teamfortress_quizzes.php) | [Repository](https://github.com/impressto/quizimodo)

Quizimodo is a **React-based quiz application** designed to make building and playing quizzes straightforward, engaging, and effective.

It includes interactive features such as **answer streaks, animations, and automatically generated cheat sheets** to enhance both the learning experience and long-term retention.

---

<img width="738" height="604" alt="quiz_example" src="https://github.com/user-attachments/assets/59d0d1a2-194b-4c2a-a522-16962cc49bc2" />

## ✨ Features

- 🎯 **Dynamic Quizzes** – Questions with multiple-choice answers.  
- �️ **Visual Learning** – Support for both quiz-level and question-specific header images.
- �🔥 **Streaks** – Encourages focus and motivation by rewarding consecutive correct answers.  
- 📄 **Cheat Sheets** – Auto-generated review sheets based on user performance.  
- 🎨 **Polished UI** – Animations and feedback for a more engaging learning experience.  
- 🌐 **Extensible Back End** – Optional score-tracking API with examples in Node, PHP, and Python.  

---

## 🛠 Tech Stack

- **React** – component-based front-end framework
- **React Hooks** – state management and interactivity
- **JavaScript (ES6+)** – modern language features
- **CSS / Animations / Framer Motion** (if used) – smooth UI effects
- **Node.js & npm** – development environment and package management

---

## 📦 Installation & Setup

Clone the repository:

```bash
git clone https://github.com/impressto/quizimodo.git
cd quizimodo
```

Install dependencies:

```bash
npm install
```
 
Run the app locally:
```bash
npm run dev
```
  
The app will be available at http://localhost:3000 by default.

## Build for production

```bash
npm run build
```

This creates an optimized production build in the /build folder.

---

## 📊 Setting Up Score Tracking API

Quizimodo supports optional score tracking to allow users to compare their performance with others. You can implement the backend API using PHP, Node.js, or Python (or whatever works for you).

### Environment Configuration

Add these variables to your `.env` file:

```
VITE_API_BASE_URL=https://your-domain.com/api  # Base URL for your API endpoints
```

### Backend Implementation Options

Example implementations for different backend technologies are provided in the `public/api_examples` directory:

#### PHP Implementation with SQLite

1. Create a directory for storing the SQLite database:

```bash
mkdir -p public/api/scores
chmod 755 public/api/scores
```

2. Copy the example PHP files from `public/api_examples` to your server:
   - `save-score.php` - Handles saving quiz scores
   - `quiz-stats.php` - Provides quiz statistics
   - `db_helper.php` - Database helper functions for SQLite integration
   - `seed-scores.php` - Generates test data for development and testing

See the [save-score.php](public/api_examples/save-score.php) and [quiz-stats.php](public/api_examples/quiz-stats.php) files for complete implementations. The system automatically falls back to file-based storage if SQLite is not available. There's also a helpful [seed-scores.php](public/api_examples/seed-scores.php) script for generating test data (see "Generating Test Data" section below).

#### Database Requirements for SQLite

The PHP implementation uses SQLite by default, but will fall back to flat files if SQLite is not available:

1. Make sure PHP has the PDO SQLite extension enabled:

```bash
# Check if PDO SQLite is available
php -m | grep sqlite
php -m | grep pdo_sqlite
```

2. If it's not available, install it:

```bash
# On Ubuntu/Debian
sudo apt-get install php-sqlite3

# On CentOS/RHEL
sudo yum install php-sqlite3

# On macOS (using Homebrew)
brew install php
```

3. Make sure the scores directory is writable:

```bash
chmod 755 public/api/scores
```

#### Node.js Implementation

1. Set up a Node.js server using Express:

```bash
mkdir -p api
cd api
npm init -y
npm install express cors body-parser better-sqlite3
```

2. Copy the [server.js](public/api_examples/server.js) example from `public/api_examples` to your server.

3. Run the server:

```bash
node server.js
```

#### Python Implementation (with Flask)

1. Set up a Python virtual environment:

```bash
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
pip install flask flask-cors sqlite3
```

2. Copy the [app.py](public/api_examples/app.py) example from `public/api_examples` to your server.

3. Run the Flask app:

```bash
python app.py
```

### Generating Test Data

Quizimodo includes a PHP script to generate test data for your quizzes. This is helpful for testing and developing the statistics display features without having to manually create many quiz attempts.

1. Navigate to your API directory:

```bash
cd public/api_examples
```

2. Run the seeder script:

```bash
php seed-scores.php              # Generate scores for new quizzes only
php seed-scores.php --force      # Generate scores for all quizzes, overwriting existing data
```

The script will:
- Automatically detect your quizzes
- Generate realistic-looking quiz scores with a bell curve distribution
- Store the scores in the SQLite database
- Properly separate statistics by both quiz ID and topic
- Manage existing data appropriately based on your options

When using SQLite (default), scores are stored in a single database file (`scores/quiz_scores.db`) with appropriate tables for quizzes and scores. If SQLite is not available, the system will automatically fall back to file-based storage with the following naming conventions:
- `{topic}_{quizId}_scores.json` - Complete score records
- `{topic}_{quizId}_summary.json` - Summary statistics
- `{topic}_{quizId}_recent.json` - Recent scores

This ensures that quizzes with the same ID but in different topics will have separate statistics.

### SQLite Database Structure

The SQLite database used for score tracking contains the following tables:

1. `quizzes` table:
   - `id` - Primary key
   - `quiz_id` - Quiz identifier string 
   - `topic` - Topic identifier string
   - `title` - Quiz title

2. `scores` table:
   - `id` - Primary key
   - `quiz_id` - Foreign key to quizzes table
   - `user_id` - Anonymous user identifier
   - `name` - Optional user name
   - `email` - Optional user email
   - `score` - Number of correct answers
   - `correct` - Number of correct answers (same as score)
   - `total` - Total number of questions
   - `timestamp` - Unix timestamp when the score was recorded
   - `date` - Formatted date and time

### Frontend Integration

The quiz application already includes built-in support for score tracking. The main components that handle this functionality are:

1. `src/scoreService.ts` - Contains functions for saving scores and fetching statistics
2. `src/QuizResult.tsx` - Displays the quiz results and statistics

To enable or disable score tracking, simply set the `VITE_API_BASE_URL` environment variable in your `.env` file.

```
VITE_API_BASE_URL=/api  # For local development with API in /public/api
# or
VITE_API_BASE_URL=https://your-domain.com/api  # For production
```

---

## 📋 Quiz JSON Format

Quizimodo uses a straightforward JSON format for defining quizzes. Each quiz is defined in its own JSON file in the `public/quizzes/[topic]` directory.

### Basic Quiz Structure

```json
{
  "title": "Quiz Title",
  "description": "Short description of the quiz content",
  "time": "15 minutes",
  "headerimage": "/topic/header.png", 
  "questions": [
    {
      "id": 1,
      "question": "What is the question?",
      "options": [
        "Option A",
        "Option B",
        "Option C",
        "Option D"
      ],
      "answer": 2,
      "explanation": "Explanation of why Option C is correct"
    }
  ]
}
```

### Advanced Quiz Features

#### Header Images

You can include header images at two levels:

1. **Quiz-level header image**: Displayed at the top of the quiz for all questions:

```json
{
  "title": "My Quiz",
  "description": "Quiz description",
  "headerimage": "/topic/header.png"
}
```

2. **Question-specific header images**: Each question can have its own unique header image that overrides the quiz-level image:

```json
{
  "id": 2,
  "headerimage": "https://example.com/images/specific-question-image.jpg",
  "question": "What is the question?",
  "options": ["Option A", "Option B", "Option C", "Option D"],
  "answer": 1
}
```

When a question has its own `headerimage`, the quiz-level header image will not be displayed for that particular question. This allows for targeted visual aids that are specific to individual questions.

#### Code Examples

You can include code examples in questions, options, and explanations using HTML with special styling classes:

```json
{
  "question": "What will this code output?",
  "example": "<div class='quiz-code-dark'><pre><span class='keyword'>function</span> <span class='function'>example</span>() {\n  <span class='function'>console</span>.<span class='function'>log</span>(<span class='string'>'Hello World'</span>);\n}</pre></div>",
  "options": [
    "It will print 'Hello World'",
    "It will return 'Hello World'",
    "Nothing, the function is never called",
    "It will throw an error"
  ]
}
```

The `example` field can be used to display code or other formatted content between the question text and the answer options. This is especially useful for coding quizzes where you want to show a code sample for the user to analyze.

#### Code Styling Classes

The following classes are available for styling code:

- `quiz-code` - Light background code block
- `quiz-code-dark` - Dark background code block (preferred)
- `keyword` - For language keywords
- `string` - For string literals
- `function` - For function names
- `comment` - For comments
- `number` - For numeric literals
- `jsx-tag`, `jsx-attribute`, `jsx-expression` - For JSX syntax

#### Inline Code in Options

You can wrap text in backticks (`) to display it as inline code, which is especially useful for HTML tags or code snippets in answer options:

```json
"options": [
  "Regular text option",
  "`<div>Some HTML Code</div>`",
  "Another text option",
  "`const x = 42;`"
]
```

#### Additional Explanation

For more complex topics, you can include a detailed explanation with example code that will be shown after the user selects an answer:

```json
{
  "explanation": "The correct answer is Option B because...",
  "exampleExplanation": "<div class='quiz-code-dark'><pre><span class='comment'>// Here's the correct implementation</span>\n<span class='keyword'>function</span> <span class='function'>betterExample</span>() {\n  <span class='keyword'>return</span> <span class='string'>'Hello World'</span>;\n}</pre></div>"
}
```

The `exampleExplanation` field is used to show example code or other formatted content after the main explanation, often to demonstrate the correct approach.

### HTML Support

All text fields (question, options, explanation) support HTML, allowing you to include:

- Formatting (`<strong>`, `<em>`, etc.)
- Lists (`<ul>`, `<ol>`, `<li>`)
- Images (`<img>`)
- Links (`<a>`)
- And other HTML elements

For security reasons, always ensure that any HTML content is properly sanitized in a production environment.

---

## 🌐 Deploying to a Web Server

To deploy Quizimodo to a web server, follow these steps:

1. Build the application:

```bash
npm run build
```

2. Upload the contents of the `dist` folder to your web server.

3. Configure your web server to serve the `index.html` file for any routes that don't match a physical file.

### Example Apache Configuration (.htaccess)

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>
```

### Embedding in an Existing Website

You can also embed Quizimodo into an existing website:

1. Build the application.
2. Copy the contents of the `dist` folder to a subdirectory of your website.
3. Use an iframe to embed the quiz:

```html
<iframe 
  src="path/to/quizimodo/index.html" 
  style="width: 100%; height: 800px; border: none;"
  title="Quiz Application"
></iframe>
```

### Loading Specific Topics and Quizzes

You can specify a particular topic and even a specific quiz to load directly when the application starts:

#### Method 1: Using HTML Data Attributes

Add data attributes to the root element:

```html
<!-- Load a specific topic -->
<div id="root" data-topic="react"></div>

<!-- Load a specific topic AND a specific quiz -->
<div id="root" data-topic="react" data-quiz="react-coding-quiz"></div>
```

#### Method 2: Using URL Hash

Add a hash to the URL to specify the quiz:

```
https://example.com/quizzes/#react-coding-quiz
```

This can be combined with the data-topic attribute to load quizzes from different topics.

#### Method 3: Using Query Parameters in an iframe

When embedding the quiz in an iframe, you can include the quiz ID in the URL:

```html
<iframe 
  src="path/to/quizimodo/index.html#react-coding-quiz" 
  style="width: 100%; height: 800px; border: none;"
  title="React Coding Quiz"
></iframe>
```

The application will:
1. First check for a hash in the URL
2. Then check for a quiz specified via data-quiz attribute
3. Fall back to showing the quiz selection screen if neither is provided

---