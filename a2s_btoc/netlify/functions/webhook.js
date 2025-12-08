import { createClient } from '@supabase/supabase-js';

// Initialize Supabase client
const supabaseUrl = process.env.VITE_SUPABASE_URL;
const supabaseKey = process.env.VITE_SUPABASE_ANON_KEY;
const supabase = createClient(supabaseUrl, supabaseKey);

exports.handler = async (event, context) => {
  // Handle GET request for webhook verification
  if (event.httpMethod === 'GET') {
    const params = new URLSearchParams(event.rawQuery);
    const mode = params.get('hub.mode');
    const token = params.get('hub.verify_token');
    const challenge = params.get('hub.challenge');

    if (mode && token) {
      if (mode === 'subscribe' && token === process.env.WEBHOOK_VERIFY_TOKEN) {
        return {
          statusCode: 200,
          body: challenge
        };
      }
      return { statusCode: 403 };
    }
  }

  // Handle POST request for webhook events
  if (event.httpMethod === 'POST') {
    try {
      const body = JSON.parse(event.body);

      if (body.object === 'page' || body.object === 'instagram') {
        for (let entry of body.entry) {
          if (entry.messaging) {
            const messaging = entry.messaging[0];
            const senderId = messaging.sender.id;
            const message = messaging.message.text;
            const timestamp = new Date(messaging.timestamp);
            const platform = body.object === 'page' ? 'Facebook' : 'Instagram';

            await supabase.from('messages').insert([{
              platform,
              sender_id: senderId,
              message,
              timestamp,
              page_id: entry.id,
              is_read: false,
              is_converted: false
            }]);
          }
        }
        return { statusCode: 200, body: 'EVENT_RECEIVED' };
      }
      return { statusCode: 404 };
    } catch (error) {
      console.error('Error processing webhook:', error);
      return { statusCode: 500 };
    }
  }
};