# Services Layer

This directory contains service modules for API communication and authentication.

## Files

### `apiClient.ts`
HTTP client singleton based on Axios with automatic token management.

**Features:**
- Automatic token injection via request interceptors
- Token storage in localStorage
- Automatic token removal on 401 responses
- Type-safe request methods (GET, POST, PUT, DELETE)

**Usage:**
```typescript
import { apiClient } from './services/apiClient';

// GET request
const data = await apiClient.get<ResponseType>('/endpoint');

// POST request
const result = await apiClient.post<ResponseType>('/endpoint', { data });

// Manual token management
apiClient.setToken('token');
const token = apiClient.getToken();
apiClient.removeToken();
```

### `authService.ts`
Authentication service that handles user login, logout, and session management.

**Methods:**
- `login(email, password)`: Authenticate user with Dolibarr
- `getCurrentUser()`: Fetch current user information
- `logout()`: Clear authentication token
- `getToken()`: Get stored authentication token

**Usage:**
```typescript
import { authService } from './services/authService';

// Login
const { token, user } = await authService.login('user@example.com', 'password');

// Get current user
const user = await authService.getCurrentUser();

// Logout
authService.logout();

// Check if logged in
const token = authService.getToken();
if (token) {
  // User is logged in
}
```

### `index.ts`
Barrel export file for cleaner imports.

**Usage:**
```typescript
// Instead of multiple imports
import { apiClient } from './services/apiClient';
import { authService } from './services/authService';

// Use single import
import { apiClient, authService } from './services';
```

## Configuration

The API base URL is configured via environment variable:

```env
VITE_API_BASE_URL=http://localhost:3000/api
```

Default value: `http://localhost:3000/api`

## Token Storage

Authentication tokens are stored in localStorage with the key `dolibarr_token`.

**Security Note:** localStorage tokens are vulnerable to XSS attacks. Consider implementing:
- Content Security Policy (CSP)
- Token expiration and refresh mechanism
- HttpOnly cookies (requires backend changes)

## Error Handling

The apiClient automatically handles 401 (Unauthorized) responses by:
1. Removing the stored token
2. Rejecting the promise with the error

Components using these services should handle errors appropriately and redirect to login when necessary.

## Integration with Stores

The authService is primarily used by the `authStore` (Zustand store) which provides:
- User state management
- Authentication status
- Login/logout methods
- Auth persistence checking

See `src/store/authStore.ts` for implementation details.
