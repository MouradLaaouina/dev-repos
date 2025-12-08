/*
  # Add product_name column to order_items table

  1. New Column
    - Add product_name to order_items table to store actual product names
    - This eliminates dependency on productStore for displaying product names
    
  2. Benefits
    - Product names are stored directly with each order item
    - No dependency on external product catalog
    - Names remain consistent even if product catalog changes
*/

-- Add product_name column to order_items table
ALTER TABLE order_items 
ADD COLUMN IF NOT EXISTS product_name text;

-- Add comment to explain the new column
COMMENT ON COLUMN order_items.product_name IS 'Actual product name stored with the order item to avoid dependency on product catalog';