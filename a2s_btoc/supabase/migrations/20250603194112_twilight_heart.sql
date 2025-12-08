/*
  # Add product and user codes
  
  1. New Columns
    - Add code column to products table
    - Add code column to users table
    
  2. Changes
    - Make code columns required
    - Add unique constraint on product codes
    - Add unique constraint on user codes
*/

-- Add code column to products table
ALTER TABLE public.products
ADD COLUMN code TEXT NOT NULL;

-- Add unique constraint to product codes
ALTER TABLE public.products
ADD CONSTRAINT products_code_key UNIQUE (code);

-- Add code column to users table
ALTER TABLE public.users
ADD COLUMN code TEXT;

-- Add unique constraint to user codes
ALTER TABLE public.users
ADD CONSTRAINT users_code_key UNIQUE (code);