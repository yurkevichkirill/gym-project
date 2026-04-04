import {User, UserTypes} from "@/types/auth.type";

export const isClient = (user: User) => {
    return user.type === UserTypes.CLIENT;
}

export const isTrainer = (user: User) => {
    return user.type === UserTypes.TRAINER;
}

export const isAdmin = (user: User) => {
    return user.type === UserTypes.ADMIN;
}

export const isManager = (user: User) => {
    return user.type === UserTypes.MANAGER;
}