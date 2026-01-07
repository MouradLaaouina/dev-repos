import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import axios from 'axios';

// Load environment variables
dotenv.config();

const app = express();
const PORT = process.env.SERVER_PORT || 3000;
const DOLIBARR_API_URL = process.env.VITE_API_BASE_URL || 'http://localhost:3000/api';

// Middleware
app.use(cors());
app.use(express.json());

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
  // In Dolibarr migration, webhooks should push to Dolibarr via the API Express
  console.log('Received Social Webhook');
  res.status(200).send('EVENT_RECEIVED');
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
  console.log('Received WhatsApp Webhook');
  res.status(200).send('EVENT_RECEIVED');
});

// Start server
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});
