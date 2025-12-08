/*
  # Add RLS policies for users table

  1. Security Changes
    - Enable RLS on users table
    - Add policy for user registration
    - Add policy for users to read their own data
    - Add policy for users to update their own data

  This migration adds the necessary Row Level Security (RLS) policies to the users table
  to allow user registration and profile management while maintaining data security.
*/

-- Enable RLS
ALTER TABLE users ENABLE ROW LEVEL SECURITY;

-- Policy for new user registration
CREATE POLICY "Allow users to insert their own profile"
  ON users
  FOR INSERT
  TO authenticated
  WITH CHECK (auth.uid() = id);

-- Policy for users to read their own data
CREATE POLICY "Users can read own profile"
  ON users
  FOR SELECT
  TO authenticated
  USING (auth.uid() = id);

-- Policy for users to update their own data
CREATE POLICY "Users can update own profile"
  ON users
  FOR UPDATE
  TO authenticated
  USING (auth.uid() = id)
  WITH CHECK (auth.uid() = id);