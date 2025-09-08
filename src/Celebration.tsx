import { useEffect, useState } from 'react';
import type { ReactElement } from 'react';
import './Celebration.css';

const Celebration = () => {
  const [particles, setParticles] = useState<ReactElement[]>([]);

  useEffect(() => {
    // Create confetti particles
    const newParticles = [];
    const colors = ['#FFD700', '#FF6347', '#4169E1', '#32CD32', '#FF69B4', '#9370DB'];
    
    // Generate random confetti
    for (let i = 0; i < 50; i++) {
      const left = Math.random() * 100;
      const top = Math.random() * 100;
      const size = Math.random() * 10 + 5;
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
  }, []);

  return (
    <div className="celebration-container">
      <div className="confetti-container">
        {particles}
      </div>
      <div className="celebration-message">
        <div className="emoji">🎉</div>
        <h3>Wow! 5 in a row!</h3>
        <div className="emoji">😄</div>
      </div>
    </div>
  );
};

export default Celebration;
