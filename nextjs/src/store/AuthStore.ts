import {makeAutoObservable, runInAction} from "mobx";
import {LoginRequest, LoginResponse, MeResponse, User} from "@/types/auth.type";
import {apiGet, apiPost} from "@/lib/apiClient";
import { ApiItemResponse } from "@/types/api-item-response.type";
import ClientType from "@/types/client/client.type";
import { ClientRegisterRequest } from "@/types/client/client-register-request.type";

export interface AuthStore {
    user: User | null;
    isAuth: boolean;
    isLoading: boolean;

    login: (payload: LoginRequest) => Promise<LoginResponse>;
    register: (payload: ClientRegisterRequest) => Promise<ApiItemResponse<ClientType>>;
    checkAuth: () => Promise<void>;
    logout: () => Promise<void>;
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

            await authStore.checkAuth();

            return res;
        } finally {
            runInAction(() => {
                authStore.isLoading = false;
            });
        }
    },

    register: async (payload) => {
        authStore.isLoading = true;

        try {
            const res = await apiPost<ApiItemResponse<ClientType>, ClientRegisterRequest>(
                '/client/registration/', 
                {
                    ...payload,
                    age: Number(payload.age)
                }
            );

            return res;
        } finally {
            runInAction(() => {
                authStore.isLoading = false;
            });
        }
    },

    checkAuth: async () => {
        authStore.isLoading = true;

        try {
            const res = await apiGet<MeResponse>('/me/');

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
        await apiPost(
            "/logout/",
            {}
        );

        authStore.user = null;
        authStore.isAuth = false;
    },
};

makeAutoObservable(authStore);