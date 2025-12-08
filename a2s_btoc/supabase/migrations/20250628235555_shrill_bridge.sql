/*
  # Update source values and constraint

  1. Changes
    - First update the constraint to include both 'CENTRE APPEL' and 'WHATSAPP'
    - Then update data to change 'CENTRE APPEL' to 'WHATSAPP'
    
  2. Fix
    - This approach avoids constraint violation errors during migration
    - Ensures existing data remains valid throughout the process
*/

-- First, drop the existing constraint
ALTER TABLE contacts DROP CONSTRAINT IF EXISTS contacts_source_check;

-- Add the updated constraint with BOTH 'CENTRE APPEL' AND 'WHATSAPP'
-- This ensures existing data remains valid during the update
ALTER TABLE contacts ADD CONSTRAINT contacts_source_check 
CHECK ((source = ANY (ARRAY[
  'PAS DÉFINI'::text, 
  'ALOUA LIFE STYLE'::text, 
  'CHAIMAA MOAD'::text, 
  'GHIZLANE CHLIKHATE'::text, 
  'HAFSSA ACHRAF'::text, 
  'HAJAR ARSALANE'::text, 
  'HIBA NAYRAS'::text, 
  'MARWA AOUB'::text, 
  'CORITA'::text, 
  'WAHIBA BOUYA'::text, 
  'YOUSSRA'::text, 
  'JAD BELGAID'::text, 
  'HIBA LAMANE'::text, 
  'WYDAD SERRI'::text, 
  'MOUNIA JAIDAR'::text, 
  'RAJAA'::text, 
  'SABAH BENSEDDIK'::text, 
  'SARA BOUBBAD'::text, 
  'LAMIE CONSEIL'::text, 
  'GHAZAL TIKTOK'::text, 
  'SARA REGRAGUI'::text, 
  'AFLATONA'::text, 
  'SARA ASTERI'::text, 
  'Maria LAZRAK'::text, 
  'RECOMMANDATION'::text, 
  'GHIZLANE ELOTMANI'::text, 
  'QUEEN BY IMANE'::text, 
  'KAOUTAR BERRANI'::text, 
  'SARAYATALK'::text, 
  'LINA ELYAHYAOUI'::text, 
  'OUIDAD LEMNIAI'::text, 
  'LEILA BOULKADDAT'::text, 
  'CHEKORS'::text, 
  'NOUHAILA BARBIE'::text, 
  'FATIYASS'::text, 
  'OUMAIMA FARAH'::text, 
  'ADS'::text, 
  'ADS WHATSAPP'::text, 
  'ADS BALI'::text, 
  'R-S'::text, 
  'HANANE KHAYATI'::text, 
  'CHAMAMOUST'::text, 
  'LAMIAA AHMADI'::text, 
  'JIHAD SABIR'::text, 
  'YASMINERIE'::text, 
  'SALMA CHOKOLATI'::text, 
  'NIRMINE BEAUTY'::text, 
  'KHADIJA SAKHI'::text, 
  'MIMI MICROBLADING'::text, 
  'HAMAKA'::text, 
  'ZINEB LAALAMI'::text, 
  'FADIL SALMA'::text, 
  'KAOUTAR RAGHAY'::text, 
  'TOUHA'::text, 
  'THOURAYA'::text, 
  'AHMED KABBAJ'::text, 
  'KHAWLA QUEEN'::text, 
  'MERIEM ASOUAB'::text, 
  'KHAOULA NAOUM'::text, 
  'AFRAH'::text, 
  'CLIENT EXISTANT'::text, 
  'En attente de réponse'::text, 
  'Sans réponse'::text,
  'Ghita MOUHIB'::text,
  'SARA EL BAKKAL'::text,
  'YOUSSRA QUEEN'::text,
  'CENTRE APPEL'::text,
  'WHATSAPP'::text,
  'Démarchage'::text,
  'Parrainage'::text
])));

-- Now update existing data to change 'CENTRE APPEL' to 'WHATSAPP'
UPDATE contacts 
SET source = 'WHATSAPP' 
WHERE source = 'CENTRE APPEL';

-- Add comment to explain the change
COMMENT ON CONSTRAINT contacts_source_check ON contacts IS 'Updated to include both CENTRE APPEL and WHATSAPP during transition';