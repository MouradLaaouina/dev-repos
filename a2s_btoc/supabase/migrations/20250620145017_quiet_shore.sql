/*
  # Clean all test orders to start fresh

  1. Data Cleanup
    - Delete all order items
    - Delete all orders
    - Delete all contacts with type_de_demande = 'Commande'
    - Reset the order_number sequence to start from 1

  2. Sequence Reset
    - Reset the BIGSERIAL sequence for order_number to start fresh

  This migration will clean all test data so you can start with a clean slate.
*/

-- Delete all order items first (due to foreign key constraints)
DELETE FROM order_items;

-- Delete all orders
DELETE FROM orders;

-- Delete all contacts that are orders (type_de_demande = 'Commande')
DELETE FROM contacts WHERE type_de_demande = 'Commande';

-- Reset the order_number sequence to start from 1
-- First, find the sequence name
DO $$
DECLARE
    seq_name TEXT;
BEGIN
    -- Get the sequence name for order_number column
    SELECT pg_get_serial_sequence('orders', 'order_number') INTO seq_name;
    
    -- Reset the sequence to start from 1
    IF seq_name IS NOT NULL THEN
        EXECUTE 'ALTER SEQUENCE ' || seq_name || ' RESTART WITH 1';
        RAISE NOTICE 'Reset sequence % to start from 1', seq_name;
    END IF;
END $$;

-- Add comment to track this cleanup
COMMENT ON TABLE orders IS 'Orders table cleaned of test data - ready for production use';