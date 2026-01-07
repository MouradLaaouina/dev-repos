# Authentication Refactor: Supabase to Dolibarr/Express API

## Overview
This document describes the refactoring of the authentication system from Supabase to Dolibarr/Express API.

## Changes Made

### 1. Created API Client Layer (`src/services/apiClient.ts`)
- Centralized HTTP client using Axios
- Automatic token injection via interceptors
- Token storage in localStorage with key `dolibarr_token`
- Automatic token removal on 401 responses
- Support for GET, POST, PUT, DELETE methods

### 2. Created Auth Service (`src/services/authService.ts`)
- `login(email, password)`: Authenticates with Dolibarr and returns user info
- `getCurrentUser()`: Fetches current user info using stored token
- `logout()`: Clears stored token
- `getToken()`: Returns stored token
- Maps Dolibarr user structure to application User type
- Handles team assignment based on `code_agence`

### 3. Refactored Auth Store (`src/store/authStore.ts`)
- **Removed**: Supabase imports and dependencies
- **Removed**: `register` method (not supported by Dolibarr API)
- **Updated**: `login` method to use authService
- **Updated**: `logout` method to use authService
- **Updated**: `checkAuth` method to use token-based authentication
- **Removed**: Supabase auth state change listener

### 4. Updated Register Form (`src/components/auth/RegisterForm.tsx`)
- Removed dependency on authStore.register
- Shows error message that registration is not available
- Redirects to login page

### 5. Environment Configuration (`.env.example`)
- Added `VITE_API_BASE_URL` for API Express base URL
- Default: `http://localhost:3000/api`

### 6. Dependencies
- Added `axios` package for HTTP requests

## API Integration

### Authentication Endpoints

#### POST /api/auth/login
**Request:**
```json
{
  "login": "user@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "succes": "ok",
  "token": "dolibarr_token_string",
  "user": {
    "id": "1",
    "login": "user",
    "firstname": "John",
    "lastname": "Doe",
    "email": "user@example.com",
    "admin": "0",
    ...
  }
}
```

#### GET /api/auth/me
**Headers:**
- `DOLAPIKEY`: User token

**Response:**
```json
{
  "status": "ok",
  "result": {
    "id": "1",
    "login": "user",
    "firstname": "John",
    "lastname": "Doe",
    "email": "user@example.com",
    ...
  }
}
```

## User Mapping

The `mapDolibarrUserToUser` function in `authService.ts` maps Dolibarr user data to the application's User type:

- **id**: User ID from Dolibarr
- **name**: Combination of firstname and lastname, fallback to login
- **email**: User email
- **role**: Mapped from admin flag ('1' → 'admin', else → 'agent')
- **code**: User login
- **codeAgence**: Team code
- **team**: Team name based on codeAgence
  - `000001` → Réseaux sociaux
  - `000002` → Centre d'appel
  - `000003` → WhatsApp
- **createdAt**: User creation date

## Backward Compatibility

The refactored authStore maintains the same interface as before:
- `user: User | null`
- `isAuthenticated: boolean`
- `loading: boolean`
- `login(email, password): Promise<boolean>`
- `logout(): Promise<void>`
- `checkAuth(): Promise<void>`

This ensures all existing components using `useAuthStore` continue to work without modification.

## Testing

Build successful with no TypeScript errors:
```bash
npm run build
npx tsc --noEmit
```

## Next Steps

1. Set up environment variable `VITE_API_BASE_URL` pointing to API Express
2. Test login flow with actual Dolibarr credentials
3. Verify token persistence across page refreshes
4. Test logout functionality
5. Consider implementing refresh token mechanism if needed

## Notes

- User registration is disabled as Dolibarr typically doesn't support self-registration
- Token is stored in localStorage (consider security implications)
- The API Express has a typo: `succes` instead of `success` in login response
- Error handling uses toast notifications for user feedback
