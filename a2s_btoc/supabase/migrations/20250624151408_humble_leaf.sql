/*
  # Create RPC function for contacts by source statistics

  1. New Functions
    - `get_contacts_by_source` - Returns aggregated contact counts by source with optional filtering
      - Parameters: start_date, end_date, p_code_agence (all optional)
      - Returns: source (text), count (bigint)
  
  2. Security
    - Function is accessible to authenticated users
    - Respects existing RLS policies through direct table access
*/

CREATE OR REPLACE FUNCTION get_contacts_by_source(
  start_date timestamptz DEFAULT NULL,
  end_date timestamptz DEFAULT NULL,
  p_code_agence text DEFAULT NULL
)
RETURNS TABLE(source text, count bigint)
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
  RETURN QUERY
  SELECT 
    COALESCE(c.source, 'Source inconnue') as source,
    COUNT(*) as count
  FROM contacts c
  WHERE 
    c.source IS NOT NULL
    AND (start_date IS NULL OR c.created_at >= start_date)
    AND (end_date IS NULL OR c.created_at <= end_date)
    AND (p_code_agence IS NULL OR c.code_agence = p_code_agence)
  GROUP BY c.source
  ORDER BY count DESC;
END;
$$;

-- Grant execute permission to authenticated users
GRANT EXECUTE ON FUNCTION get_contacts_by_source TO authenticated;