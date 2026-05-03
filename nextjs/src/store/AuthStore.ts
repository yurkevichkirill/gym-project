import {makeAutoObservable, runInAction} from "mobx";
import {LoginRequest, LoginResponse, MeResponse, User} from "@/types/auth.type";
import {apiDelete, apiGet, apiPatch, apiPost} from "@/lib/apiClient";
import ClientEditType from "@/types/client/client-edit.type";
import {ApiItemResponse} from "@/types/api-item-response.type";

export interface AuthStore {
    user: User | null;
    isAuth: boolean;
    isLoading: boolean;

    login: (payload: LoginRequest) => Promise<LoginResponse>;
    checkAuth: () => Promise<void>;
    logout: () => Promise<void>;
    editUser: (payload: ClientEditType) => Promise<void>;
    deleteUser: () => Promise<void>;
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

    editUser: async (data: ClientEditType) => {
        runInAction(() => {
            authStore.isLoading = true;
        });

        try {
            const res = await apiPatch<ApiItemResponse<User>>('/me/', data);

            runInAction(() => {
                authStore.user = res.data;
            });

        } catch (e) {
            console.error(e);
            throw e;
        } finally {
            runInAction(() => {
                authStore.isLoading = false;
            });
        }
    },

    deleteUser: async () => {
        runInAction(() => {
            authStore.isLoading = true;
        });

        try {
            await apiDelete<null>('/me/');

            runInAction(() => {
                authStore.user = null;
                authStore.isAuth = false;
            });
        } catch (e) {
            console.error(e);
            throw e;
        } finally {
            runInAction(() => {
                authStore.isLoading = false;
            });
        }
    }
};

makeAutoObservable(authStore);