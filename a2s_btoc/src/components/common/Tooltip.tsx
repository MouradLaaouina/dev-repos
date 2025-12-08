import React, { useState, useEffect, ReactNode } from 'react';

interface TooltipProps {
  content: string;
  children: ReactNode;
  position?: 'top' | 'bottom' | 'left' | 'right';
}

export const Tooltip: React.FC<TooltipProps> = ({ 
  content, 
  children, 
  position = 'top' 
}) => {
  const [isVisible, setIsVisible] = useState(false);
  const [tooltipPosition, setTooltipPosition] = useState({ x: 0, y: 0 });
  const [tooltipElement, setTooltipElement] = useState<HTMLDivElement | null>(null);

  const showTooltip = (e: React.MouseEvent) => {
    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
    
    let x = 0;
    let y = 0;
    
    switch (position) {
      case 'top':
        x = rect.left + rect.width / 2;
        y = rect.top;
        break;
      case 'bottom':
        x = rect.left + rect.width / 2;
        y = rect.bottom;
        break;
      case 'left':
        x = rect.left;
        y = rect.top + rect.height / 2;
        break;
      case 'right':
        x = rect.right;
        y = rect.top + rect.height / 2;
        break;
    }
    
    setTooltipPosition({ x, y });
    setIsVisible(true);
  };

  const hideTooltip = () => {
    setIsVisible(false);
  };

  useEffect(() => {
    if (isVisible && tooltipElement) {
      const tooltipRect = tooltipElement.getBoundingClientRect();
      let x = tooltipPosition.x;
      let y = tooltipPosition.y;
      
      switch (position) {
        case 'top':
          x -= tooltipRect.width / 2;
          y -= tooltipRect.height + 5;
          break;
        case 'bottom':
          x -= tooltipRect.width / 2;
          y += 5;
          break;
        case 'left':
          x -= tooltipRect.width + 5;
          y -= tooltipRect.height / 2;
          break;
        case 'right':
          x += 5;
          y -= tooltipRect.height / 2;
          break;
      }
      
      // Ensure tooltip stays within viewport
      if (x < 5) x = 5;
      if (y < 5) y = 5;
      if (x + tooltipRect.width > window.innerWidth - 5) {
        x = window.innerWidth - tooltipRect.width - 5;
      }
      if (y + tooltipRect.height > window.innerHeight - 5) {
        y = window.innerHeight - tooltipRect.height - 5;
      }
      
      tooltipElement.style.left = `${x}px`;
      tooltipElement.style.top = `${y}px`;
    }
  }, [isVisible, tooltipElement, tooltipPosition, position]);

  return (
    <div className="relative inline-block">
      <div 
        onMouseEnter={showTooltip}
        onMouseLeave={hideTooltip}
        className="inline-block"
      >
        {children}
      </div>
      
      {isVisible && (
        <div
          ref={setTooltipElement}
          className="fixed z-50 px-2 py-1 text-xs font-medium text-white bg-black bg-opacity-80 rounded pointer-events-none"
          style={{ 
            left: tooltipPosition.x, 
            top: tooltipPosition.y,
            transform: 'translate(-50%, -100%)'
          }}
        >
          {content}
        </div>
      )}
    </div>
  );
};