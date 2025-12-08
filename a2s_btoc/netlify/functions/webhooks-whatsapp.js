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
    'Access-Control-Allow-Methods': 'POST, OPTIONS'
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
    console.log('Received Wassenger webhook payload:', JSON.stringify(body, null, 2));

    // Extract fields from Wassenger webhook format
    const data = body.data;
    if (!data) {
      console.error('Missing data object in webhook payload');
      return {
        statusCode: 400,
        headers,
        body: JSON.stringify({ 
          error: 'Missing data object',
          details: 'Webhook payload must contain a data object'
        })
      };
    }

    const user_phone = data.fromNumber;
    const timestamp = data.timestamp;
    
    // Handle different message types
    let message_text = '';
    
    switch (data.type) {
      case 'text':
        message_text = data.body || '';
        break;
      case 'image':
        message_text = data.media?.caption || '[Image]';
        break;
      case 'video':
        message_text = data.media?.caption || '[Video]';
        break;
      case 'audio':
        message_text = '[Audio message]';
        break;
      case 'document':
        message_text = `[Document: ${data.media?.filename || 'file'}]`;
        break;
      case 'location':
        message_text = '[Location shared]';
        break;
      case 'contact':
        message_text = '[Contact shared]';
        break;
      default:
        message_text = `[${data.type || 'Unknown'} message]`;
    }

    // Validate required fields
    if (!user_phone || !timestamp) {
      console.error('Missing required fields:', { user_phone, timestamp });
      return {
        statusCode: 400,
        headers,
        body: JSON.stringify({ 
          error: 'Missing required fields',
          details: 'data.fromNumber and data.timestamp are required'
        })
      };
    }

    // Only process inbound messages
    if (data.flow !== 'inbound') {
      console.log('Ignoring outbound message');
      return {
        statusCode: 200,
        headers,
        body: JSON.stringify({ 
          message: 'Outbound message ignored'
        })
      };
    }

    // Find an agent to assign the conversation to (first available agent)
    const { data: agents, error: agentError } = await supabase
      .from('users')
      .select('id')
      .eq('role', 'agent')
      .limit(1);

    if (agentError) {
      console.error('Error finding agent:', agentError);
    }

    const agent_id = agents && agents.length > 0 ? agents[0].id : null;

    // Insert conversation record into the conversations table
    const { data: insertedData, error } = await supabase
      .from('conversations')
      .insert([
        {
          user_phone: user_phone,
          message_text: message_text,
          timestamp: timestamp, // UNIX timestamp as provided
          direction: 'in',
          agent_id: agent_id,
          is_read: false
        }
      ])
      .select();

    if (error) {
      console.error('Database error:', error);
      return {
        statusCode: 500,
        headers,
        body: JSON.stringify({ 
          error: 'Failed to save conversation',
          details: error.message 
        })
      };
    }

    console.log('Successfully saved conversation:', insertedData);

    return {
      statusCode: 200,
      headers,
      body: JSON.stringify({ 
        success: true, 
        message: 'Conversation saved successfully',
        data: insertedData[0]
      })
    };

  } catch (error) {
    console.error('Webhook processing error:', error);
    
    // Handle JSON parsing errors specifically
    if (error instanceof SyntaxError) {
      return {
        statusCode: 400,
        headers,
        body: JSON.stringify({ 
          error: 'Invalid JSON format',
          details: 'Request body must be valid JSON'
        })
      };
    }

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