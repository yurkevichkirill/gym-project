export const Roles = {
    USER: "ROLE_USER",
    CLIENT: "ROLE_CLIENT",
    TRAINER: "ROLE_TRAINER",
    ADMIN: "ROLE_ADMIN",
    MANAGER: "ROLE_MANAGER",
} as const;

export type Role = typeof Roles[keyof typeof Roles];