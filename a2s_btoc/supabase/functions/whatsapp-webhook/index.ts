import { createClient } from 'npm:@supabase/supabase-js@2';

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Methods": "GET, POST, PUT, DELETE, OPTIONS",
  "Access-Control-Allow-Headers": "Content-Type, Authorization",
};

Deno.serve(async (req: Request) => {
  // Handle CORS preflight requests
  if (req.method === "OPTIONS") {
    return new Response(null, {
      status: 200,
      headers: corsHeaders,
    });
  }

  if (req.method !== "POST") {
    return new Response("Method not allowed", { 
      status: 405,
      headers: corsHeaders 
    });
  }

  try {
    // Initialize Supabase client
    const supabaseUrl = Deno.env.get('SUPABASE_URL')!;
    const supabaseKey = Deno.env.get('SUPABASE_ANON_KEY')!;
    const supabase = createClient(supabaseUrl, supabaseKey);

    // Parse the incoming JSON body
    const body = await req.json();
    console.log('Received webhook payload:', JSON.stringify(body, null, 2));

    // Extract required fields with error handling
    const userPhone = body?.contact?.phone;
    const messageText = body?.message?.body;
    const timestamp = body?.message?.timestamp;

    // Validate required fields
    if (!userPhone || !messageText || !timestamp) {
      console.error('Missing required fields:', { userPhone, messageText, timestamp });
      return new Response(
        JSON.stringify({ 
          error: 'Missing required fields: contact.phone, message.body, or message.timestamp' 
        }),
        { 
          status: 400,
          headers: { ...corsHeaders, 'Content-Type': 'application/json' }
        }
      );
    }

    // Find agent by phone number (assuming agent's WhatsApp number is stored in users table)
    // For now, we'll assign to the first agent or admin
    const { data: agents, error: agentError } = await supabase
      .from('users')
      .select('id')
      .eq('role', 'agent')
      .limit(1);

    if (agentError) {
      console.error('Error finding agent:', agentError);
      return new Response(
        JSON.stringify({ error: 'Failed to find agent' }),
        { 
          status: 500,
          headers: { ...corsHeaders, 'Content-Type': 'application/json' }
        }
      );
    }

    const agentId = agents && agents.length > 0 ? agents[0].id : null;

    // Insert conversation record
    const { data, error } = await supabase
      .from('conversations')
      .insert([
        {
          user_phone: userPhone,
          message_text: messageText,
          timestamp: parseInt(timestamp),
          direction: 'in',
          agent_id: agentId,
          is_read: false
        }
      ])
      .select();

    if (error) {
      console.error('Database error:', error);
      return new Response(
        JSON.stringify({ error: 'Failed to save conversation' }),
        { 
          status: 500,
          headers: { ...corsHeaders, 'Content-Type': 'application/json' }
        }
      );
    }

    console.log('Successfully saved conversation:', data);

    return new Response(
      JSON.stringify({ 
        success: true, 
        message: 'Conversation saved successfully',
        data: data[0]
      }),
      {
        status: 200,
        headers: { ...corsHeaders, 'Content-Type': 'application/json' }
      }
    );

  } catch (error) {
    console.error('Webhook processing error:', error);
    return new Response(
      JSON.stringify({ 
        error: 'Internal server error',
        details: error.message 
      }),
      { 
        status: 500,
        headers: { ...corsHeaders, 'Content-Type': 'application/json' }
      }
    );
  }
});