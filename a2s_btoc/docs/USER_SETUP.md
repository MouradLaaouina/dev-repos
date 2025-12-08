# User Setup Guide

## Creating Users in the System

### Method 1: Self Registration
1. Navigate to `/register` in your application
2. Fill out the registration form with:
   - Name
   - Email  
   - Password
3. The user will be created with `agent` role by default

### Method 2: Direct Database Creation (Admin)
If you have database access, you can create users directly:

```sql
-- First create the auth user
INSERT INTO auth.users (id, email, encrypted_password, email_confirmed_at, created_at, updated_at)
VALUES (
  gen_random_uuid(),
  'agent@example.com',
  crypt('password123', gen_salt('bf')),
  now(),
  now(),
  now()
);

-- Then create the profile in users table
INSERT INTO users (id, name, email, role, code_agence, created_at)
VALUES (
  (SELECT id FROM auth.users WHERE email = 'agent@example.com'),
  'Agent Name',
  'agent@example.com', 
  'agent',
  '000001', -- Team code (000001=Réseaux sociaux, 000002=Centre appel, 000003=WhatsApp)
  now()
);
```

### Method 3: Admin User Creation (Recommended)
Create an admin user first, then use the admin interface:

```sql
-- Create admin user
INSERT INTO auth.users (id, email, encrypted_password, email_confirmed_at, created_at, updated_at)
VALUES (
  gen_random_uuid(),
  'admin@example.com',
  crypt('admin123', gen_salt('bf')),
  now(),
  now(),
  now()
);

INSERT INTO users (id, name, email, role, created_at)
VALUES (
  (SELECT id FROM auth.users WHERE email = 'admin@example.com'),
  'Administrator',
  'admin@example.com',
  'admin',
  now()
);
```

## Team Structure

The system uses team codes for organization:
- `000001` - Réseaux sociaux (Social Media Team)
- `000002` - Centre d'appel (Call Center Team)  
- `000003` - WhatsApp Team

## Role Permissions

### Agent (`agent`)
- Access to personal dashboard (`/dashboard/agent-dashboard`)
- Can add contacts (`/dashboard/contacts/new`)
- Manage their own clients (`/dashboard/clients`)
- View their orders (`/dashboard/orders`)
- WhatsApp management (`/dashboard/whatsapp`)

### Call Center (`callcenter`)
- Access to agent dashboard (`/dashboard/agent-dashboard`)
- Call center interface (`/dashboard/call-center`)
- Lead management and calling tools
- Call follow-up forms

### Admin (`admin`)
- Full access to all features
- Advanced statistics (`/dashboard/advanced-stats`)
- All team data visibility
- User management capabilities

## Getting Started

1. **Start the application**: `npm run dev`
2. **Navigate to**: `http://localhost:5173`
3. **Register or login** with appropriate credentials
4. **Access role-specific dashboards** based on your user role

## Default Login Credentials

If you've set up the database with sample data, you might have:
- Admin: `admin@example.com` / `admin123`
- Agent: `agent@example.com` / `password123`

## Troubleshooting

### Can't see any data?
- Make sure you're logged in with the correct role
- Check that your user has the appropriate `code_agence` for team filtering
- Verify database connections are working

### No access to certain features?
- Check your user role in the database
- Ensure RLS policies are properly configured
- Verify your user has the necessary permissions

### Call center features not working?
- Make sure your user role is `callcenter` or `admin`
- Check that you have access to the call center dashboard
- Verify MicroSIP is installed for phone integration