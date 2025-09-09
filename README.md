# React Quiz App

A customizable quiz application built with React, TypeScript, and Vite that allows users to take quizzes on various topics.

## Features

- Load quizzes from different topic folders
- Randomized answer options for better replayability
- Visual feedback for correct/incorrect answers
- Celebration animations for answer streaks
- Responsive design for mobile and desktop

## How to Use

### Running Locally

1. Clone the repository
2. Install dependencies: `npm install`
3. Start the development server: `npm run dev`

### Building for Production

```bash
npm run build
```

This will create a `dist` folder with the production build.

### Hosting Multiple Quiz Topics

The app supports loading quizzes from different topic folders. You can specify the topic in one of two ways:

#### 1. Using the HTML data attribute

```html
<!-- In your HTML file -->
<div id="root" data-topic="javascript"></div>
```

#### 2. Using URL parameters

```
https://your-site.com/quiz?topic=javascript
```

### Quiz File Structure

For each topic, you should have a directory structure like this:

```
/public/quizzes/[topic]/
  └── quizzes-meta.json
  └── quiz1.json
  └── quiz2.json
  └── ...
```

The `quizzes-meta.json` file should have this structure:

```json
{
  "quizzes": [
    {
      "id": "quiz1",
      "file": "quiz1.json"
    },
    {
      "id": "quiz2",
      "file": "quiz2.json"
    }
  ]
}
```

Each quiz file should have this structure:

```json
{
  "title": "Quiz Title",
  "description": "Quiz description",
  "questions": [
    {
      "id": 1,
      "question": "Question text?",
      "options": [
        "Option 1",
        "Option 2",
        "Option 3",
        "Option 4"
      ],
      "answer": 2 // Index of the correct option (0-based)
    },
    // More questions...
  ]
}
```

## Production Deployment

When deployed in production, the app automatically detects the environment and uses the correct URL structure:

- Development: `/quizzes/[topic]/...`
- Production: `https://impressto.ca/quizzes/public/quizzes/[topic]/...`

      // Remove tseslint.configs.recommended and replace with this
      ...tseslint.configs.recommendedTypeChecked,
      // Alternatively, use this for stricter rules
      ...tseslint.configs.strictTypeChecked,
      // Optionally, add this for stylistic rules
      ...tseslint.configs.stylisticTypeChecked,

      // Other configs...
    ],
    languageOptions: {
      parserOptions: {
        project: ['./tsconfig.node.json', './tsconfig.app.json'],
        tsconfigRootDir: import.meta.dirname,
      },
      // other options...
    },
  },
])
```

You can also install [eslint-plugin-react-x](https://github.com/Rel1cx/eslint-react/tree/main/packages/plugins/eslint-plugin-react-x) and [eslint-plugin-react-dom](https://github.com/Rel1cx/eslint-react/tree/main/packages/plugins/eslint-plugin-react-dom) for React-specific lint rules:

```js
// eslint.config.js
import reactX from 'eslint-plugin-react-x'
import reactDom from 'eslint-plugin-react-dom'

export default tseslint.config([
  globalIgnores(['dist']),
  {
    files: ['**/*.{ts,tsx}'],
    extends: [
      // Other configs...
      // Enable lint rules for React
      reactX.configs['recommended-typescript'],
      // Enable lint rules for React DOM
      reactDom.configs.recommended,
    ],
    languageOptions: {
      parserOptions: {
        project: ['./tsconfig.node.json', './tsconfig.app.json'],
        tsconfigRootDir: import.meta.dirname,
      },
      // other options...
    },
  },
])
```
