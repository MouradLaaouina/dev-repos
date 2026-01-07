import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

// Load environment variables
dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.SERVER_PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());

// Serve static files from the React app
const distPath = path.join(__dirname, '../dist');
app.use(express.static(distPath));

// The "catchall" handler: for any request that doesn't
// match one above, send back React's index.html file.
app.get('*', (req, res) => {
  res.sendFile(path.join(distPath, 'index.html'));
});

// Start server
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});
