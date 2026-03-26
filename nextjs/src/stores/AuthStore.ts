import {makeAutoObservable, runInAction} from "mobx";
import {LoginRequest, LoginResponse, MeResponse, User} from "@/types/auth.type";
import {apiGet, apiPost} from "@/lib/apiClient";

export interface AuthStore {
    user: User | null;
    isAuth: boolean;
    isLoading: boolean;

    login: (payload: LoginRequest) => Promise<void>;
    checkAuth: () => Promise<void>;
    logout: () => void;
}

export const authStore: AuthStore = {
    user: null,
    isAuth: false,
    isLoading: false,

    login: async (payload) => {
        authStore.isLoading = true;

        try {
            const res = await apiPost<LoginResponse, LoginRequest>(
                '/login/',
                payload
            );

            runInAction(() => {
                authStore.user = {
                    id: 0,
                    email: res.data.user,
                    roles: [],
                };
                authStore.isAuth = true;
            });
        } finally {
            runInAction(() => {
                authStore.isLoading = false;
            });
        }
    },

    checkAuth: async () => {
        authStore.isLoading = true;

        try {
            const res = await apiGet<MeResponse>('/me');

            runInAction(() => {
                authStore.user = res.data;
                authStore.isAuth = true;
            });
        } catch {
            runInAction(() => {
                authStore.user = null;
                authStore.isAuth = false;
            });
        } finally {
            runInAction(() => {
                authStore.isLoading = false;
            });
        }
    },

    logout: async () => {
        await fetch('/api/logout/', {
            method: 'POST',
            credentials: 'include',
        })
        authStore.user = null;
        authStore.isAuth = false;
    },
};

makeAutoObservable(authStore);