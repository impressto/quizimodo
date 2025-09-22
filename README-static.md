# Quiz App - Static Version

This is a static version of the React Quiz App that can be hosted on GitHub Pages or any static hosting service.

## Features

- ✅ Static file hosting compatibility
- ✅ Local storage for user scores
- ✅ Offline quiz statistics (combines static data with user scores)
- ✅ No server dependencies
- ✅ GitHub Pages deployment ready

## Deployment Options

### Option 1: GitHub Pages (Automatic)

1. Push to the `static-app-version` branch
2. GitHub Actions will automatically build and deploy
3. Your app will be available at `https://username.github.io/repository-name`

### Option 2: Manual Build for Static Hosting

1. Build the static version:
   ```bash
   yarn build:static
   ```

2. Deploy the `dist` folder to your static hosting service (Netlify, Vercel, etc.)

## Environment Configuration

The app uses different configurations based on the `VITE_STATIC_MODE` environment variable:

- **Static Mode** (`.env.static`): Uses localStorage and static JSON files
- **Server Mode** (`.env`): Uses API endpoints for data persistence

## Data Storage in Static Mode

- **User Scores**: Stored in browser's localStorage
- **Quiz Statistics**: Combines static baseline data from `public/quiz-stats.json` with user's local scores
- **Anonymous ID**: Generated and stored per browser session

## File Structure for Static Hosting

```
dist/
├── index.html
├── assets/
│   ├── index.js
│   └── index.css
├── quizzes/
│   └── react/
│       ├── react-coding-quiz.json
│       └── react-hook-basics.json
└── quiz-stats.json
```

## Customization

To add more quizzes or modify statistics:

1. Add quiz JSON files to `public/quizzes/[topic]/`
2. Update `public/quiz-stats.json` with baseline statistics
3. Rebuild with `yarn build:static`

## Browser Compatibility

Works in all modern browsers with localStorage support. No server-side dependencies required.
