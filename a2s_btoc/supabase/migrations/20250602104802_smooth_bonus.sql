-- Create connected_social_accounts table
CREATE TABLE public.connected_social_accounts (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  platform TEXT NOT NULL CHECK (platform IN ('Facebook', 'Instagram', 'WhatsApp')),
  platform_page_id TEXT NOT NULL,
  platform_name TEXT NOT NULL,
  access_token TEXT NOT NULL,
  webhook_subscribed BOOLEAN NOT NULL DEFAULT false,
  is_active BOOLEAN NOT NULL DEFAULT true,
  status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'active', 'error')),
  connected_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  
  -- Ensure unique platform + page combination per user
  UNIQUE(user_id, platform, platform_page_id)
);

-- Enable RLS
ALTER TABLE public.connected_social_accounts ENABLE ROW LEVEL SECURITY;

-- Create policies for connected_social_accounts
CREATE POLICY "Users can view their own connected accounts"
  ON public.connected_social_accounts
  FOR SELECT
  TO authenticated
  USING (
    user_id = auth.uid() OR 
    EXISTS (
      SELECT 1 FROM users 
      WHERE users.id = auth.uid() 
      AND users.role = 'admin'
    )
  );

CREATE POLICY "Users can insert their own connected accounts"
  ON public.connected_social_accounts
  FOR INSERT
  TO authenticated
  WITH CHECK (user_id = auth.uid());

CREATE POLICY "Users can update their own connected accounts"
  ON public.connected_social_accounts
  FOR UPDATE
  TO authenticated
  USING (user_id = auth.uid())
  WITH CHECK (user_id = auth.uid());

CREATE POLICY "Users can delete their own connected accounts"
  ON public.connected_social_accounts
  FOR DELETE
  TO authenticated
  USING (user_id = auth.uid());

-- Create updated_at trigger
CREATE TRIGGER update_connected_social_accounts_updated_at
  BEFORE UPDATE ON public.connected_social_accounts
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

-- Create indexes for better query performance
CREATE INDEX idx_connected_social_accounts_user_id ON public.connected_social_accounts(user_id);
CREATE INDEX idx_connected_social_accounts_platform ON public.connected_social_accounts(platform);
CREATE INDEX idx_connected_social_accounts_status ON public.connected_social_accounts(status);

-- Add comment to explain table purpose
COMMENT ON TABLE public.connected_social_accounts IS 'Stores connected social media accounts (Facebook Pages, Instagram accounts, WhatsApp Business numbers) and their access tokens';