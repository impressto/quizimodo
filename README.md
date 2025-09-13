# Quizimodo

[Live Demo](https://impressto.ca/teamfortress_quizzes.php) | [Repository](https://github.com/impressto/quizimodo)

Quizimodo is a **React-based quiz application** designed to make building and playing quizzes straightforward, engaging, and effective.

It includes interactive features such as **answer streaks, animations, and automatically generated cheat sheets** to enhance both the learning experience and long-term retention.

---

<img width="738" height="604" alt="quiz_example" src="https://github.com/user-attachments/assets/59d0d1a2-194b-4c2a-a522-16962cc49bc2" />

## ✨ Features

- 🎯 **Dynamic Quizzes** – Questions with multiple-choice answers.  
- 🔥 **Streaks** – Encourages focus and motivation by rewarding consecutive correct answers.  
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

#### PHP Implementation

1. Create a directory for storing scores:

```bash
mkdir -p public/api/scores
chmod 755 public/api/scores
```

2. Copy the example PHP files from `public/api_examples` to your server:
   - `save-score.php` - Handles saving quiz scores
   - `quiz-stats.php` - Provides quiz statistics

See the [save-score.php](public/api_examples/save-score.php) and [quiz-stats.php](public/api_examples/quiz-stats.php) files for complete implementations.

#### Node.js Implementation

1. Set up a Node.js server using Express:

```bash
mkdir -p api
cd api
npm init -y
npm install express cors body-parser fs-extra
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
pip install flask flask-cors
```

2. Copy the [app.py](public/api_examples/app.py) example from `public/api_examples` to your server.

3. Run the Flask app:

```bash
python app.py
```

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
