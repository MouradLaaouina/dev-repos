# User Setup Guide

## Creating Users in the System

Users are now managed directly in Dolibarr. To grant access to the BToC Front:

1.  **Create a User in Dolibarr**:
    - Go to Setup -> Users & Groups.
    - Create a new user or select an existing one.
2.  **Enable BToC Access Module**:
    - Ensure the `BTOC Access Management` module is enabled in Dolibarr.
3.  **Assign Permissions**:
    - In the user's "Permissions" tab, find the `btocaccess` section.
    - Grant the "Access BToC Application" permission.
4.  **Set Team Code (optional)**:
    - Use the user's "Login" or a custom field if needed for team filtering.
    - Default team codes used in the front:
        - `000001` - Réseaux sociaux
        - `000002` - Centre d'appel
        - `000003` - WhatsApp

## Role Permissions (Dolibarr Based)

Permissions are now mapped from Dolibarr:
- **Admin**: Users with the "Admin" flag in Dolibarr. Full access to all features.
- **Agent**: Regular users with the BToC Access permission. Access to their own data.
- **Superviseur**: Users with specific permissions to view team data.

## Getting Started

1.  **Start the application**: `./docker-start.sh` (from the project root)
2.  **Navigate to**: `http://localhost:8083`
3.  **Login** with your Dolibarr credentials.

## Troubleshooting

### Can't login?
- Ensure the API Express is running and can connect to Dolibarr.
- Verify your DOLAPIKEY is valid if using direct API access.
- Check that the `btocaccess` module is active in Dolibarr.
