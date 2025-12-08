import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import { createClient } from '@supabase/supabase-js';

// Load environment variables
dotenv.config();

const app = express();
const PORT = process.env.SERVER_PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());

// Initialize Supabase client
const supabaseUrl = process.env.VITE_SUPABASE_URL;
const supabaseKey = process.env.VITE_SUPABASE_ANON_KEY;
const supabase = createClient(supabaseUrl, supabaseKey);

// Webhook verification for Facebook/Instagram
app.get('/webhook', (req, res) => {
  const mode = req.query['hub.mode'];
  const token = req.query['hub.verify_token'];
  const challenge = req.query['hub.challenge'];

  if (mode && token) {
    if (mode === 'subscribe' && token === process.env.WEBHOOK_VERIFY_TOKEN) {
      console.log('Webhook verified');
      res.status(200).send(challenge);
    } else {
      res.sendStatus(403);
    }
  }
});

// Webhook handler for Facebook/Instagram
app.post('/webhook', async (req, res) => {
  const { body } = req;

  // Verify this is a page subscription
  if (body.object === 'page' || body.object === 'instagram') {
    try {
      for (let entry of body.entry) {
        // Handle messages
        if (entry.messaging) {
          const messaging = entry.messaging[0];
          const senderId = messaging.sender.id;
          const message = messaging.message.text;
          const timestamp = new Date(messaging.timestamp);
          const platform = body.object === 'page' ? 'Facebook' : 'Instagram';

          // Store message in Supabase
          const { data, error } = await supabase
            .from('messages')
            .insert([
              {
                platform,
                sender_id: senderId,
                message,
                timestamp,
                page_id: entry.id,
                is_read: false,
                is_converted: false
              }
            ]);

          if (error) throw error;
        }
      }
      res.status(200).send('EVENT_RECEIVED');
    } catch (error) {
      console.error('Error processing webhook:', error);
      res.sendStatus(500);
    }
  } else {
    res.sendStatus(404);
  }
});

// WhatsApp webhook verification
app.get('/webhook/whatsapp', (req, res) => {
  const mode = req.query['hub.mode'];
  const token = req.query['hub.verify_token'];
  const challenge = req.query['hub.challenge'];

  if (mode && token) {
    if (mode === 'subscribe' && token === process.env.WHATSAPP_VERIFY_TOKEN) {
      console.log('WhatsApp webhook verified');
      res.status(200).send(challenge);
    } else {
      res.sendStatus(403);
    }
  }
});

// WhatsApp webhook handler
app.post('/webhook/whatsapp', async (req, res) => {
  try {
    const { body } = req;
    
    if (body.object === 'whatsapp_business_account') {
      for (let entry of body.entry) {
        for (let change of entry.changes) {
          if (change.field === 'messages') {
            const message = change.value.messages[0];
            
            // Store message in Supabase
            const { data, error } = await supabase
              .from('messages')
              .insert([
                {
                  platform: 'WhatsApp',
                  sender_id: message.from,
                  message: message.text.body,
                  timestamp: new Date(message.timestamp * 1000),
                  is_read: false,
                  is_converted: false
                }
              ]);

            if (error) throw error;
          }
        }
      }
      res.status(200).send('EVENT_RECEIVED');
    } else {
      res.sendStatus(404);
    }
  } catch (error) {
    console.error('Error processing WhatsApp webhook:', error);
    res.sendStatus(500);
  }
});

// Start server
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});