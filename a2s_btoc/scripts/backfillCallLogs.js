// Backfill script to create call logs for existing orders from team 000002
// This ensures all "Commande" leads appear correctly in the call center dashboard
import { createClient } from '@supabase/supabase-js';
import dotenv from 'dotenv';

dotenv.config();

// Initialize Supabase client with service_role key for admin access
const supabaseUrl = process.env.VITE_SUPABASE_URL;
const supabaseServiceKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

if (!supabaseUrl || !supabaseServiceKey) {
  console.error('❌ Missing environment variables. Please set VITE_SUPABASE_URL and SUPABASE_SERVICE_ROLE_KEY');
  process.exit(1);
}

const supabase = createClient(supabaseUrl, supabaseServiceKey);

async function backfillCallLogs() {
  console.log('🔄 Starting call logs backfill process...');
  
  try {
    // 1. Find all contacts from team 000002 with type_de_demande = 'Commande'
    console.log('🔍 Finding all "Commande" contacts from team 000002...');
    
    const { data: contacts, error: contactsError } = await supabase
      .from('contacts')
      .select('*')
      .eq('type_de_demande', 'Commande')
      .eq('code_agence', '000002');
    
    if (contactsError) {
      throw contactsError;
    }
    
    console.log(`✅ Found ${contacts?.length || 0} "Commande" contacts from team 000002`);
    
    if (!contacts || contacts.length === 0) {
      console.log('ℹ️ No contacts to process. Exiting.');
      return;
    }
    
    // 2. Process each contact
    let processedCount = 0;
    let createdCount = 0;
    let skippedCount = 0;
    
    for (const contact of contacts) {
      processedCount++;
      
      // Log progress every 10 contacts
      if (processedCount % 10 === 0) {
        console.log(`🔄 Processing contact ${processedCount}/${contacts.length}...`);
      }
      
      // 3. Check if a call log already exists for this contact with status 'Commande'
      const { data: existingLogs, error: logsError } = await supabase
        .from('call_logs')
        .select('*')
        .eq('client_id', contact.id)
        .eq('call_status', 'Commande');
      
      if (logsError) {
        console.error(`❌ Error checking existing logs for contact ${contact.id}:`, logsError);
        continue;
      }
      
      // Skip if a call log already exists
      if (existingLogs && existingLogs.length > 0) {
        skippedCount++;
        continue;
      }
      
      // 4. Check if there's a corresponding lead in call_center_leads
      let leadId = null;
      
      const { data: leads, error: leadsError } = await supabase
        .from('call_center_leads')
        .select('id')
        .eq('code_client', contact.client_code || contact.id);
      
      if (!leadsError && leads && leads.length > 0) {
        leadId = leads[0].id;
      }
      
      // 5. Create a new call log
      const callLogData = {
        client_id: contact.id,
        lead_id: leadId,
        call_status: 'Commande',
        satisfaction_level: 5, // Assume highest satisfaction for orders
        interested: 'Oui',
        call_date: contact.created_at,
        agent_id: contact.agent_id,
        notes: 'Commande créée automatiquement (backfill)'
      };
      
      const { data: newLog, error: createError } = await supabase
        .from('call_logs')
        .insert([callLogData])
        .select();
      
      if (createError) {
        console.error(`❌ Error creating call log for contact ${contact.id}:`, createError);
        continue;
      }
      
      createdCount++;
    }
    
    // 6. Report results
    console.log('\n✅ Backfill process completed successfully!');
    console.log(`📊 Summary:`);
    console.log(`   - Total contacts processed: ${processedCount}`);
    console.log(`   - Call logs created: ${createdCount}`);
    console.log(`   - Contacts skipped (already had call logs): ${skippedCount}`);
    
  } catch (error) {
    console.error('❌ Error during backfill process:', error);
  }
}

// Run the backfill function
backfillCallLogs()
  .then(() => {
    console.log('🏁 Backfill script execution completed');
    process.exit(0);
  })
  .catch(error => {
    console.error('❌ Fatal error during backfill execution:', error);
    process.exit(1);
  });