import { useEffect } from 'react';
import { Loader2 } from 'lucide-react';
import logo from '../../assets/A2S_Logo_Vector.svg'

export default function LoadingPage ({ onLoadComplete }) {
  useEffect(() => {
    const timer = setTimeout(() => {
      onLoadComplete();
    }, 2000);
    return () => clearTimeout(timer);
  }, [onLoadComplete]);

  return (
    <div className="fixed inset-0 bg-gradient-to-br from-green-600 to-green-800 flex items-center justify-center">
      <div className="text-center">
        <div className="w-32 h-32 bg-white rounded-3xl shadow-2xl flex items-center justify-center mb-6 mx-auto p-4">
          <img 
            src={logo}
            alt="A2S Logo" 
            className="w-full h-full object-contain"
          />
        </div>
        <Loader2 className="w-8 h-8 text-white animate-spin mx-auto" />
      </div>
    </div>
  );
};