import { useEffect, useState } from 'react';
import type { ReactElement } from 'react';
import './Celebration.css';

interface CelebrationProps {
  streakLevel: 'basic' | 'amazing' | 'mindblowing';
  congratsImage?: string;
}

const Celebration = ({ streakLevel = 'basic', congratsImage }: CelebrationProps) => {
  const [particles, setParticles] = useState<ReactElement[]>([]);

  useEffect(() => {
    // Create confetti particles
    const newParticles = [];
    const colors = ['#FFD700', '#FF6347', '#4169E1', '#32CD32', '#FF69B4', '#9370DB'];
    
    // Determine particle count based on streak level
    let particleCount = 50; // Default for 'basic'
    if (streakLevel === 'amazing') particleCount = 75;
    if (streakLevel === 'mindblowing') particleCount = 100;
    
    // Generate random confetti
    for (let i = 0; i < particleCount; i++) {
      const left = Math.random() * 100;
      const top = Math.random() * 100;
      // Make particles larger for higher streak levels
      const sizeMultiplier = streakLevel === 'mindblowing' ? 1.5 : (streakLevel === 'amazing' ? 1.2 : 1);
      const size = (Math.random() * 10 + 5) * sizeMultiplier;
      const color = colors[Math.floor(Math.random() * colors.length)];
      const animationDuration = Math.random() * 2 + 1;
      
      const style = {
        left: `${left}%`,
        top: `${top}%`,
        width: `${size}px`,
        height: `${size}px`,
        backgroundColor: color,
        animationDuration: `${animationDuration}s`,
      };
      
      newParticles.push(
        <div 
          key={i} 
          className="confetti" 
          style={style}
        />
      );
    }
    
    setParticles(newParticles);
  }, [streakLevel]);

  const renderContent = () => {
    switch(streakLevel) {
      case 'amazing':
        return (
          <>
            <div className="emoji">🌟</div>
            <h3>Amazing! 10 in a row!</h3>
            <div className="emoji">🤩</div>
          </>
        );
      case 'mindblowing':
        return (
          <>
            <div className="emoji">🔥</div>
            <h3>Mind Blown! 20 in a row!</h3>
            <div className="emoji">🤯</div>
          </>
        );
      default:
        return (
          <>
            <div className="emoji">🎉</div>
            <h3>Wow! 5 in a row!</h3>
            <div className="emoji">😄</div>
          </>
        );
    }
  };

  return (
    <div className="celebration-container">
      <div className="confetti-container">
        {particles}
      </div>
      <div
        className="celebration-message"
        style={
          congratsImage
            ? {
                backgroundImage: `url(${congratsImage})`,
                backgroundSize: 'cover',
                backgroundPosition: 'center',
                backgroundRepeat: 'no-repeat',
                borderRadius: '1em',
                padding: '2em',
                minHeight: '220px',
                color: '#fff',
                boxShadow: '0 0 20px rgba(0,0,0,0.3)',
                position: 'relative',
                zIndex: 2,
              }
            : undefined
        }
      >
        {renderContent()}
      </div>
    </div>
  );
};

export default Celebration;
