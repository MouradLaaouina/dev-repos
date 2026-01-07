import React, { useState, useEffect } from 'react';
import { Facebook, Instagram, MessageSquare, AlertCircle, CheckCircle2, XCircle } from 'lucide-react';
import { useAuthStore } from '../../store/authStore';
import toast from 'react-hot-toast';

const ConnectAccountsPage: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const [connectedAccounts, setConnectedAccounts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchConnectedAccounts();
  }, []);

  const fetchConnectedAccounts = async () => {
    try {
      // In Dolibarr migration, this is currently not supported
      setConnectedAccounts([]);
    } catch (error) {
      console.error('Error fetching connected accounts:', error);
      toast.error('Failed to load connected accounts');
    } finally {
      setLoading(false);
    }
  };

  const handleFacebookLogin = () => {
    const FB_APP_ID = import.meta.env.VITE_FB_APP_ID;
    const redirectUri = `${window.location.origin}/auth/facebook/callback`;
    const scope = [
      'pages_messaging',
      'pages_show_list',
      'whatsapp_business_management',
      'whatsapp_business_messaging',
      'instagram_basic',
      'instagram_manage_messages',
      'business_management'
    ].join(',');

    window.location.href = `https://www.facebook.com/v18.0/dialog/oauth?client_id=${FB_APP_ID}&redirect_uri=${redirectUri}&scope=${scope}&response_type=code`;
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'active':
        return <CheckCircle2 className="h-5 w-5 text-success-500" />;
      case 'pending':
        return <AlertCircle className="h-5 w-5 text-warning-500" />;
      case 'error':
        return <XCircle className="h-5 w-5 text-danger-500" />;
      default:
        return null;
    }
  };

  const getPlatformIcon = (platform: string) => {
    switch (platform) {
      case 'Facebook':
        return <Facebook className="h-6 w-6 text-blue-600" />;
      case 'Instagram':
        return <Instagram className="h-6 w-6 text-pink-600" />;
      case 'WhatsApp':
        return <MessageSquare className="h-6 w-6 text-green-600" />;
      default:
        return null;
    }
  };

  if (loading) {
    return (
      <div className="py-6">
        <div className="text-center">
          <div className="animate-spin h-8 w-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
          <p className="mt-2 text-gray-600">Loading connected accounts...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="py-6">
      <div className="max-w-4xl mx-auto">
        <div className="mb-8">
          <h1 className="text-2xl font-bold text-gray-900">Connect Your Social Accounts</h1>
          <p className="mt-2 text-gray-600">
            Connect your Facebook Pages, Instagram accounts, and WhatsApp Business numbers to manage all your messages in one place.
          </p>
        </div>

        <div className="mb-8">
          <button
            onClick={handleFacebookLogin}
            className="btn btn-primary flex items-center gap-2"
          >
            <Facebook className="h-5 w-5" />
            Connect with Facebook
          </button>
          <p className="mt-2 text-sm text-gray-500">
            This will allow you to connect Facebook Pages, Instagram accounts, and WhatsApp Business numbers.
          </p>
        </div>

        <div className="space-y-4">
          <h2 className="text-lg font-semibold text-gray-900">Connected Accounts</h2>
          
          {connectedAccounts.length === 0 ? (
            <div className="bg-gray-50 rounded-lg p-6 text-center">
              <p className="text-gray-500">No accounts connected yet.</p>
              <p className="text-sm text-gray-400 mt-1">
                Click the button above to connect your first account.
              </p>
            </div>
          ) : (
            <div className="grid gap-4">
              {connectedAccounts.map((account) => (
                <div
                  key={account.id}
                  className="bg-white rounded-lg border border-gray-200 p-4 flex items-center justify-between"
                >
                  <div className="flex items-center gap-4">
                    {getPlatformIcon(account.platform)}
                    <div>
                      <h3 className="font-medium text-gray-900">
                        {account.platform_name}
                      </h3>
                      <p className="text-sm text-gray-500">
                        Connected {new Date(account.connected_at).toLocaleDateString()}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    {getStatusIcon(account.status)}
                    <span className="text-sm capitalize text-gray-700">
                      {account.status}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default ConnectAccountsPage;
