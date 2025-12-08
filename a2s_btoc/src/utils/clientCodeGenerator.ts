import { supabase } from '../lib/supabase';

/**
 * Generates the next available client code in the format "000001" to "999999"
 * by finding the highest existing code and incrementing it, with uniqueness verification
 */
export const generateNextClientCode = async (): Promise<string> => {
  try {
    console.log('🔄 Generating next client code...');
    
    // Query to find the highest existing client_code
    const { data, error } = await supabase
      .from('contacts')
      .select('client_code')
      .not('client_code', 'is', null)
      .order('client_code', { ascending: false })
      .limit(1);
      
    if (error) {
      console.error('❌ Error fetching highest client code:', error);
      throw error;
    }
    
    let nextCode = 1; // Default starting code
    
    if (data && data.length > 0 && data[0].client_code) {
      // Parse the highest existing code and increment it
      const highestCode = parseInt(data[0].client_code);
      if (!isNaN(highestCode)) {
        nextCode = highestCode + 1;
      }
    }
    
    // Maximum attempts to find a unique code
    const maxAttempts = 100;
    let attempts = 0;
    
    while (attempts < maxAttempts) {
      // Format as 6-digit string with leading zeros
      const candidateCode = nextCode.toString().padStart(6, '0');
      
      // Check if this code already exists
      const { data: existingData, error: checkError } = await supabase
        .from('contacts')
        .select('id')
        .eq('client_code', candidateCode)
        .limit(1);
        
      if (checkError) {
        console.error('❌ Error checking client code uniqueness:', checkError);
        throw checkError;
      }
      
      // If no existing record found, this code is unique
      if (!existingData || existingData.length === 0) {
        console.log(`✅ Generated unique client code: ${candidateCode}`);
        return candidateCode;
      }
      
      // Code exists, try the next one
      nextCode++;
      attempts++;
      console.log(`⚠️ Code ${candidateCode} already exists, trying next...`);
    }
    
    // If we've exhausted all attempts, throw an error
    throw new Error(`Failed to generate unique client code after ${maxAttempts} attempts`);
    
  } catch (error) {
    console.error('❌ Error generating client code:', error);
    // Fallback to a random code in case of error
    const fallbackCode = Math.floor(100000 + Math.random() * 900000).toString();
    console.log(`⚠️ Using fallback client code: ${fallbackCode}`);
    return fallbackCode;
  }
};