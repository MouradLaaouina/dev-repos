import { apiClient } from './apiClient';
import { User } from '../types';

interface LoginResponse {
  succes: string;
  token: string;
  user: DolibarrUser;
}

interface UserInfoResponse {
  status: string;
  result: DolibarrUser;
}

interface DolibarrUser {
  id: string;
  login?: string;
  lastname?: string;
  firstname?: string;
  email?: string;
  admin?: string;
  entity?: string;
  datec?: string;
  [key: string]: any;
}

const mapDolibarrUserToUser = (dolibarrUser: DolibarrUser): User => {
  const fullName = [dolibarrUser.firstname, dolibarrUser.lastname]
    .filter(Boolean)
    .join(' ') || dolibarrUser.login || 'User';

  let role: User['role'] = 'agent';
  if (dolibarrUser.admin === '1') {
    role = 'admin';
  }

  let team = '';
  const codeAgence = dolibarrUser.code_agence || dolibarrUser.codeAgence;
  if (codeAgence === '000001') team = 'Réseaux sociaux';
  else if (codeAgence === '000002') team = 'Centre d\'appel';
  else if (codeAgence === '000003') team = 'WhatsApp';

  return {
    id: dolibarrUser.id,
    name: fullName,
    email: dolibarrUser.email || '',
    role,
    code: dolibarrUser.login,
    codeAgence,
    team,
    createdAt: dolibarrUser.datec ? new Date(dolibarrUser.datec) : new Date(),
  };
};

class AuthService {
  async login(email: string, password: string): Promise<{ token: string; user: User }> {
    const response = await apiClient.post<LoginResponse>('/auth/login', {
      login: email,
      password,
    });

    if (response.succes === 'ok' || response.token) {
      apiClient.setToken(response.token);
      const user = mapDolibarrUserToUser(response.user);
      return { token: response.token, user };
    }

    throw new Error('Login failed');
  }

  async getCurrentUser(): Promise<User> {
    const response = await apiClient.get<UserInfoResponse>('/auth/me');

    if (response.status === 'ok' && response.result) {
      return mapDolibarrUserToUser(response.result);
    }

    throw new Error('Failed to get user info');
  }

  logout(): void {
    apiClient.removeToken();
  }

  getToken(): string | null {
    return apiClient.getToken();
  }
}

export const authService = new AuthService();
