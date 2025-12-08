import React, { useState } from 'react';
import { supabase } from '../../lib/supabase';
import { RefreshCw, Database, Send, CheckCircle, XCircle } from 'lucide-react';

const DebugPanel: React.FC = () => {
  const [conversations, setConversations] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);
  const [testResult, setTestResult] = useState<string>('');
  const [webhookTest, setWebhookTest] = useState<string>('');

  const checkDatabase = async () => {
    setLoading(true);
    try {
      const { data, error, count } = await supabase
        .from('conversations')
        .select('*', { count: 'exact' })
        .order('created_at', { ascending: false })
        .limit(10);

      if (error) throw error;

      setConversations(data || []);
      setTestResult(`✅ Database connection OK. Found ${count} conversations total.`);
    } catch (error) {
      console.error('Database error:', error);
      setTestResult(`❌ Database error: ${error.message}`);
    } finally {
      setLoading(false);
    }
  };

  const testWebhook = async () => {
    setLoading(true);
    try {
      const testPayload = {
        data: {
          fromNumber: "+212600000000",
          body: "Test message from debug panel",
          timestamp: Math.floor(Date.now() / 1000)
        }
      };

      const response = await fetch('/webhooks/whatsapp', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(testPayload)
      });

      const result = await response.text();
      
      if (response.ok) {
        setWebhookTest(`✅ Webhook test successful: ${result}`);
        // Refresh the conversations after successful test
        setTimeout(checkDatabase, 1000);
      } else {
        setWebhookTest(`❌ Webhook test failed (${response.status}): ${result}`);
      }
    } catch (error) {
      setWebhookTest(`❌ Webhook test error: ${error.message}`);
    } finally {
      setLoading(false);
    }
  };

  const clearConversations = async () => {
    if (!confirm('Are you sure you want to delete all conversations? This cannot be undone.')) {
      return;
    }

    setLoading(true);
    try {
      const { error } = await supabase
        .from('conversations')
        .delete()
        .neq('id', '00000000-0000-0000-0000-000000000000'); // Delete all

      if (error) throw error;

      setTestResult('✅ All conversations cleared');
      setConversations([]);
    } catch (error) {
      setTestResult(`❌ Error clearing conversations: ${error.message}`);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="p-6 bg-white rounded-lg shadow-sm border border-gray-200">
      <h2 className="text-lg font-semibold mb-4">WhatsApp Debug Panel</h2>
      
      <div className="space-y-4">
        {/* Database Test */}
        <div className="flex gap-2">
          <button
            onClick={checkDatabase}
            disabled={loading}
            className="btn btn-primary flex items-center gap-2"
          >
            <Database className="h-4 w-4" />
            Check Database
          </button>
          
          <button
            onClick={testWebhook}
            disabled={loading}
            className="btn btn-outline flex items-center gap-2"
          >
            <Send className="h-4 w-4" />
            Test Webhook
          </button>

          <button
            onClick={clearConversations}
            disabled={loading}
            className="btn btn-danger flex items-center gap-2"
          >
            <XCircle className="h-4 w-4" />
            Clear All
          </button>
        </div>

        {/* Results */}
        {testResult && (
          <div className={`p-3 rounded-md ${testResult.includes('✅') ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'}`}>
            {testResult}
          </div>
        )}

        {webhookTest && (
          <div className={`p-3 rounded-md ${webhookTest.includes('✅') ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'}`}>
            {webhookTest}
          </div>
        )}

        {/* Conversations List */}
        {conversations.length > 0 && (
          <div className="mt-4">
            <h3 className="font-medium mb-2">Recent Conversations:</h3>
            <div className="space-y-2 max-h-60 overflow-y-auto">
              {conversations.map((conv) => (
                <div key={conv.id} className="p-2 bg-gray-50 rounded text-sm">
                  <div className="flex justify-between items-start">
                    <div>
                      <strong>{conv.user_phone}</strong>
                      <p className="text-gray-600">{conv.message_text}</p>
                    </div>
                    <div className="text-xs text-gray-500">
                      {new Date(conv.created_at).toLocaleString()}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Instructions */}
        <div className="mt-6 p-4 bg-blue-50 rounded-md">
          <h3 className="font-medium text-blue-900 mb-2">Debug Steps:</h3>
          <ol className="text-sm text-blue-800 space-y-1">
            <li>1. Click "Check Database" to see current conversations</li>
            <li>2. Click "Test Webhook" to send a test message</li>
            <li>3. Send a real WhatsApp message to your webhook URL</li>
            <li>4. Check if the message appears in the database</li>
          </ol>
          
          <div className="mt-3 p-2 bg-blue-100 rounded text-xs">
            <strong>Webhook URL:</strong> {window.location.origin}/webhooks/whatsapp
          </div>
        </div>
      </div>
    </div>
  );
};

export default DebugPanel;