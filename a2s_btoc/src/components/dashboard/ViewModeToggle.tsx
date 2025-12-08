import React from 'react';
import { BarChart2, FileText } from 'lucide-react';

interface ViewModeToggleProps {
  viewMode: 'chart' | 'text';
  onChange: (mode: 'chart' | 'text') => void;
}

const ViewModeToggle: React.FC<ViewModeToggleProps> = ({ viewMode, onChange }) => {
  return (
    <div className="flex items-center bg-gray-100 rounded-md p-1 w-fit">
      <button
        onClick={() => onChange('chart')}
        className={`flex items-center gap-1 px-3 py-1 rounded-md text-xs font-medium transition-colors ${
          viewMode === 'chart'
            ? 'bg-white text-primary-700 shadow-sm'
            : 'text-gray-600 hover:text-gray-900'
        }`}
      >
        <BarChart2 className="h-3 w-3" />
        <span>Graphique</span>
      </button>
      <button
        onClick={() => onChange('text')}
        className={`flex items-center gap-1 px-3 py-1 rounded-md text-xs font-medium transition-colors ${
          viewMode === 'text'
            ? 'bg-white text-primary-700 shadow-sm'
            : 'text-gray-600 hover:text-gray-900'
        }`}
      >
        <FileText className="h-3 w-3" />
        <span>Texte</span>
      </button>
    </div>
  );
};

export default ViewModeToggle;