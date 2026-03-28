export interface User {
    id: number;
    email: string;
    roles: string[];
}

export const Roles = {
    USER: "ROLE_USER",
    CLIENT: "ROLE_CLIENT",
    TRAINER: "ROLE_TRAINER",
    ADMIN: "ROLE_ADMIN",
    MANAGER: "ROLE_MANAGER",
} as const;

export type Role = typeof Roles[keyof typeof Roles];

export interface LoginRequest {
    email: string;
    password: string;
}

export interface LoginResponse {
    data: {
        user: string;
    };
}

export interface MeResponse {
    data: User;
}

export interface ApiError {
    message: string;
}