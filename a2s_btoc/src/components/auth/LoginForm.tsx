import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Mail, Lock, LogIn, ChevronRight } from 'lucide-react';
import { useAuthStore } from '../../store/authStore';
import toast from 'react-hot-toast';
import { cn } from '../../utils/cn';

const LoginForm: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const navigate = useNavigate();
  const login = useAuthStore((state) => state.login);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    try {
      const success = await login(email, password);
      
      if (success) {
        toast.success('Bon retour !');
        navigate('/dashboard');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen flex bg-white">
      {/* Left side - Login Form */}
      <div className="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
        <div className="mx-auto w-full max-w-sm lg:w-96 animate-fade-in">
          <div className="flex items-center gap-3 mb-10">
            <div className="h-12 w-12 bg-primary-600 rounded-2xl flex items-center justify-center shadow-lg shadow-primary-200">
              <img 
                src="/logo-removebg-preview.png" 
                alt="Logo" 
                className="h-8 w-auto brightness-200"
              />
            </div>
            <div>
              <h1 className="text-xl font-bold text-secondary-900 leading-none">A2S CRM</h1>
              <p className="text-xs text-primary-600 font-bold uppercase tracking-wider mt-1">Digital Leads Platform</p>
            </div>
          </div>

          <div>
            <h2 className="text-3xl font-extrabold text-secondary-900">Bienvenue</h2>
            <p className="mt-2 text-sm text-secondary-500">
              Veuillez vous connecter pour accéder à votre tableau de bord.
            </p>
          </div>

          <div className="mt-10">
            <form onSubmit={handleSubmit} className="space-y-6">
              <div>
                <label htmlFor="email" className="block text-sm font-semibold text-secondary-700">
                  Adresse e-mail
                </label>
                <div className="mt-1 relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Mail className="h-5 w-5 text-secondary-400" />
                  </div>
                  <input
                    id="email"
                    name="email"
                    type="email"
                    autoComplete="email"
                    required
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    className="appearance-none block w-full pl-10 pr-3 py-3 border border-secondary-200 rounded-xl shadow-sm placeholder-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent sm:text-sm transition-all"
                    placeholder="nom@entreprise.com"
                  />
                </div>
              </div>

              <div>
                <label htmlFor="password" className="block text-sm font-semibold text-secondary-700">
                  Mot de passe
                </label>
                <div className="mt-1 relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Lock className="h-5 w-5 text-secondary-400" />
                  </div>
                  <input
                    id="password"
                    name="password"
                    type="password"
                    autoComplete="current-password"
                    required
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    className="appearance-none block w-full pl-10 pr-3 py-3 border border-secondary-200 rounded-xl shadow-sm placeholder-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent sm:text-sm transition-all"
                    placeholder="••••••••"
                  />
                </div>
              </div>

              <div className="flex items-center justify-between">
                <div className="flex items-center">
                  <input
                    id="remember-me"
                    name="remember-me"
                    type="checkbox"
                    className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-secondary-300 rounded"
                  />
                  <label htmlFor="remember-me" className="ml-2 block text-sm text-secondary-600">
                    Se souvenir de moi
                  </label>
                </div>

                <div className="text-sm">
                  <a href="#" className="font-medium text-primary-600 hover:text-primary-500">
                    Mot de passe oublié ?
                  </a>
                </div>
              </div>

              <div>
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className={cn(
                    "w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all transform active:scale-[0.98]",
                    isSubmitting && "opacity-75 cursor-not-allowed"
                  )}
                >
                  {isSubmitting ? (
                    <div className="flex items-center gap-2">
                      <div className="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                      <span>Authentification...</span>
                    </div>
                  ) : (
                    <div className="flex items-center gap-2">
                      <span>Se connecter</span>
                      <ChevronRight className="h-4 w-4" />
                    </div>
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      {/* Right side - Image/Accent */}
      <div className="hidden lg:block relative flex-1 w-0 bg-secondary-900 overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-primary-600/20 to-secondary-900/90 z-10"></div>
        <img
          className="absolute inset-0 h-full w-full object-cover"
          src="https://images.unsplash.com/photo-1557804506-669a67965ba0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1974&q=80"
          alt="CRM Background"
        />
        <div className="absolute inset-0 flex flex-col justify-center px-12 z-20">
          <div className="max-w-md">
            <h2 className="text-4xl font-bold text-white mb-6">Optimisez votre gestion de leads avec A2S</h2>
            <p className="text-lg text-secondary-200">
              Une plateforme robuste et intuitive conçue pour Alliance Synergie Santé.
            </p>
            <div className="mt-10 grid grid-cols-2 gap-6">
              <div className="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/10">
                <p className="text-2xl font-bold text-white">100%</p>
                <p className="text-xs text-secondary-300 uppercase tracking-wider mt-1">Sécurisé</p>
              </div>
              <div className="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/10">
                <p className="text-2xl font-bold text-white">24/7</p>
                <p className="text-xs text-secondary-300 uppercase tracking-wider mt-1">Disponible</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default LoginForm;
