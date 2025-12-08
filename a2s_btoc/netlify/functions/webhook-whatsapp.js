import { createClient } from '@supabase/supabase-js';

const supabaseUrl = process.env.VITE_SUPABASE_URL;
const supabaseKey = process.env.VITE_SUPABASE_ANON_KEY;

// Validate environment variables
if (!supabaseUrl || !supabaseKey) {
  console.error('Missing Supabase environment variables:', { 
    hasUrl: !!supabaseUrl, 
    hasKey: !!supabaseKey 
  });
}

const supabase = supabaseUrl && supabaseKey ? createClient(supabaseUrl, supabaseKey) : null;

exports.handler = async (event, context) => {
  // Handle CORS
  const headers = {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Headers': 'Content-Type',
    'Access-Control-Allow-Methods': 'GET, POST, OPTIONS'
  };

  if (event.httpMethod === 'OPTIONS') {
    return {
      statusCode: 200,
      headers,
      body: ''
    };
  }

  if (event.httpMethod !== 'POST') {
    return {
      statusCode: 405,
      headers,
      body: JSON.stringify({ error: 'Method not allowed' })
    };
  }

  try {
    // Check if Supabase is properly configured
    if (!supabase) {
      console.error('Supabase client not initialized - missing environment variables');
      return {
        statusCode: 500,
        headers,
        body: JSON.stringify({ 
          error: 'Server configuration error',
          details: 'Database connection not available'
        })
      };
    }

    // Parse the incoming JSON body
    const body = JSON.parse(event.body);
    console.log('Received WhatsApp webhook payload:', JSON.stringify(body, null, 2));

    // Extract message data from WhatsApp webhook format
    let userPhone, messageText, timestamp;

    // Handle different WhatsApp webhook formats
    if (body.entry && body.entry[0] && body.entry[0].changes) {
      // Standard WhatsApp Business API format
      const change = body.entry[0].changes[0];
      if (change.value && change.value.messages && change.value.messages[0]) {
        const message = change.value.messages[0];
        userPhone = message.from;
        messageText = message.text ? message.text.body : 'Media message';
        timestamp = message.timestamp;
      }
    } else if (body.contact && body.message) {
      // Custom webhook format
      userPhone = body.contact.phone;
      messageText = body.message.body;
      timestamp = body.message.timestamp;
      } else if (body.data && body.data.fromNumber) {
  // Wassenger format
  userPhone = body.data.fromNumber;
  messageText = body.data.body || '[non-text message]';
  timestamp = body.data.timestamp;

    } else {
      console.error('Unknown webhook format:', body);
      return {
        statusCode: 400,
        headers,
        body: JSON.stringify({ 
          error: 'Invalid webhook format',
          details: 'Unable to parse message data'
        })
      };
    }

    // Validate required fields
    if (!userPhone || !messageText || !timestamp) {
      console.error('Missing required fields:', { userPhone, messageText, timestamp });
      return {
        statusCode: 400,
        headers,
        body: JSON.stringify({ 
          error: 'Missing required fields',
          details: 'phone, message, and timestamp are required'
        })
      };
    }

    // Convert timestamp to proper format
    const messageTimestamp = typeof timestamp === 'string' ? 
      new Date(parseInt(timestamp) * 1000) : 
      new Date(timestamp * 1000);

    // Insert message into the messages table (same as Facebook/Instagram)
    const { data, error } = await supabase
      .from('messages')
      .insert([
        {
          platform: 'WhatsApp',
          sender_id: userPhone,
          sender_name: userPhone, // Use phone as name initially
          message: messageText,
          timestamp: messageTimestamp,
          is_read: false,
          is_converted: false
        }
      ])
      .select();

    if (error) {
      console.error('Database error:', error);
      return {
        statusCode: 500,
        headers,
        body: JSON.stringify({ 
          error: 'Failed to save message',
          details: error.message 
        })
      };
    }

    console.log('Successfully saved WhatsApp message:', data);

    return {
      statusCode: 200,
      headers,
      body: JSON.stringify({ 
        success: true, 
        message: 'WhatsApp message saved successfully',
        data: data[0]
      })
    };

  } catch (error) {
    console.error('Webhook processing error:', error);
    return {
      statusCode: 500,
      headers,
      body: JSON.stringify({ 
        error: 'Internal server error',
        details: error.message 
      })
    };
  }
};