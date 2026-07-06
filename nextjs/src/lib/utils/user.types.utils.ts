import {
    AccountRole,
    CurrentUser,
    Role,
    Roles,
} from "@/types/auth.type";

export type AccountPath = "/me" | "/admin";

export const hasRole = (user: CurrentUser, role: Role): boolean => {
    return user.roles.includes(role);
};

export const isClient = (user: CurrentUser): boolean => {
    return hasRole(user, Roles.CLIENT);
};

export const isTrainer = (user: CurrentUser): boolean => {
    return hasRole(user, Roles.TRAINER);
};

export const isAdmin = (user: CurrentUser): boolean => {
    return hasRole(user, Roles.ADMIN);
};

export const getAccountRole = (user: CurrentUser): AccountRole | null => {
    if (isAdmin(user)) {
        return Roles.ADMIN;
    }

    if (isTrainer(user)) {
        return Roles.TRAINER;
    }

    if (isClient(user)) {
        return Roles.CLIENT;
    }

    return null;
};

export const getAccountPath = (user: CurrentUser): AccountPath | null => {
    const role = getAccountRole(user);

    if (role === Roles.ADMIN) {
        return "/admin";
    }

    if (role === Roles.TRAINER) {
        return "/me";
    }

    if (role === Roles.CLIENT) {
        return "/me";
    }

    return null;
};
