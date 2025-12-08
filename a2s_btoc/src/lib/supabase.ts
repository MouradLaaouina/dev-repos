import { createClient } from '@supabase/supabase-js';

// Get environment variables with fallbacks for development
const supabaseUrl = import.meta.env.VITE_SUPABASE_URL;
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY;

let supabase;

try {
  if (!supabaseUrl || !supabaseAnonKey) {
    throw new Error('Missing Supabase configuration. Please check your .env file and ensure VITE_SUPABASE_URL and VITE_SUPABASE_ANON_KEY are set correctly.');
  }

  // Validate URL format
  try {
    new URL(supabaseUrl);
  } catch (urlError) {
    throw new Error(`Invalid VITE_SUPABASE_URL format: ${supabaseUrl}. Please ensure it's a valid URL.`);
  }

  const client = createClient(supabaseUrl, supabaseAnonKey, {
    auth: {
      persistSession: true,
      storageKey: 'sb-auth-token',
      detectSessionInUrl: true,
      autoRefreshToken: true,
    }
  });

  // Test the connection
  client.auth.onAuthStateChange((event, session) => {
    console.log('Supabase connection status:', event, session ? 'Connected' : 'Disconnected');
  });

  supabase = client;
} catch (error) {
  console.error('Failed to initialize Supabase client:', error);
  
  // Show error message in the root element
  const rootElement = document.getElementById('root');
  if (rootElement) {
    rootElement.innerHTML = `
      <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: Inter, system-ui, sans-serif;">
        <div style="text-align: center; padding: 2rem; max-width: 600px;">
          <h1 style="color: #1f2937; font-size: 1.875rem; margin-bottom: 1rem;">Configuration Error</h1>
          <p style="color: #6b7280; margin-bottom: 2rem;">
            ${error.message}
          </p>
          <div style="background: #f3f4f6; padding: 1rem; border-radius: 0.5rem; text-align: left; font-family: monospace; font-size: 0.875rem;">
            <p><strong>Required environment variables:</strong></p>
            <p>VITE_SUPABASE_URL=${supabaseUrl || 'NOT_SET'}</p>
            <p>VITE_SUPABASE_ANON_KEY=${supabaseAnonKey ? '[SET]' : 'NOT_SET'}</p>
          </div>
        </div>
      </div>
    `;
  }
  
  throw error;
}

export { supabase };