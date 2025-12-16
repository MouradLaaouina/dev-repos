export interface User {
    id: string;
    name: string;
    email: string;
    role: 'agent' | 'admin';
    codeAgence: string;
    team: string;
    createdAt: Date;
}
