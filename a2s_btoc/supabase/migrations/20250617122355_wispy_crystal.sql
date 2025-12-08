/*
  # Fix infinite recursion in users table RLS policies

  1. Problem
    - Current RLS policies on users table are causing infinite recursion
    - Policies are querying the same users table they're protecting
    - This creates a circular dependency

  2. Solution
    - Drop existing problematic policies
    - Create new policies that don't reference the users table recursively
    - Use auth.uid() directly for user identification
    - Store role information in auth.users.raw_user_meta_data for admin checks

  3. New Policies
    - Users can read their own profile using auth.uid() = id
    - Users can update their own profile using auth.uid() = id
    - Users can insert their own profile using auth.uid() = id
    - Remove recursive admin policies temporarily (will need to be handled differently)
*/

-- Drop all existing policies on users table
DROP POLICY IF EXISTS "Admins can read all users" ON users;
DROP POLICY IF EXISTS "Admins can update all users" ON users;
DROP POLICY IF EXISTS "Users can insert their own profile" ON users;
DROP POLICY IF EXISTS "Users can read own profile" ON users;
DROP POLICY IF EXISTS "Users can update own profile" ON users;

-- Create non-recursive policies
CREATE POLICY "Users can read own profile"
  ON users
  FOR SELECT
  TO authenticated
  USING (auth.uid() = id);

CREATE POLICY "Users can update own profile"
  ON users
  FOR UPDATE
  TO authenticated
  USING (auth.uid() = id)
  WITH CHECK (auth.uid() = id);

CREATE POLICY "Users can insert their own profile"
  ON users
  FOR INSERT
  TO authenticated
  WITH CHECK (auth.uid() = id);

-- Temporary policy to allow admin access without recursion
-- This uses a simple approach where we check if the user's email is in a predefined admin list
-- You should replace this with your actual admin email addresses
CREATE POLICY "Admin users can read all users"
  ON users
  FOR SELECT
  TO authenticated
  USING (
    auth.uid() = id OR 
    auth.jwt() ->> 'email' IN ('admin@example.com', 'your-admin-email@domain.com')
  );

CREATE POLICY "Admin users can update all users"
  ON users
  FOR UPDATE
  TO authenticated
  USING (
    auth.uid() = id OR 
    auth.jwt() ->> 'email' IN ('admin@example.com', 'your-admin-email@domain.com')
  )
  WITH CHECK (
    auth.uid() = id OR 
    auth.jwt() ->> 'email' IN ('admin@example.com', 'your-admin-email@domain.com')
  );