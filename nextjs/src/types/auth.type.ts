export interface User {
    id: number;
    email: string;
    type: UserTypes;
    firstName: string;
    lastName: string;
    phone: string;
    createdAt: string;
    blockedAt: string | null;
    deletedAt: string | null;
    isActive: boolean;
}

export const UserTypes = {
    CLIENT: "client",
    TRAINER: "trainer",
    ADMIN: "admin",
    MANAGER: "manager",
} as const;

export type UserTypes = typeof UserTypes[keyof typeof UserTypes];

export const Roles = {
    USER: "ROLE_USER",
    CLIENT: "ROLE_CLIENT",
    TRAINER: "ROLE_TRAINER",
    ADMIN: "ROLE_ADMIN",
} as const;

export type Role = typeof Roles[keyof typeof Roles];
export type AccountRole = typeof Roles.CLIENT | typeof Roles.TRAINER | typeof Roles.ADMIN;

export interface CurrentUser {
    id: number;
    email: string;
    roles: Role[];
}

export interface LoginRequest {
    email: string;
    password: string;
}

export interface LoginResponse {
    data: {
        user: string;
    };
}

export interface CurrentUserResponse {
    data: CurrentUser;
}

export type MeResponse = CurrentUserResponse;

export interface ApiError {
    message: string;
}
