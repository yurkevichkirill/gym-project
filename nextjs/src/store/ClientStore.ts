import {makeAutoObservable, runInAction} from "mobx";
import {authStore} from "@/store/AuthStore";
import ClientEditType from "@/types/client/client-edit.type";
import {apiDelete, apiPatch, apiPost} from "@/lib/apiClient";
import {ApiItemResponse} from "@/types/api-item-response.type";
import {User} from "@/types/auth.type";
import ClientActivateType from "@/types/client/clientActivate.type";
import ClientType from "@/types/client/client.type";

export interface ClientStore {
    isLoading: boolean;

    update: (payload: ClientEditType) => Promise<void>;
    delete: () => Promise<void>;
    activate: (payload: ClientActivateType) => Promise<void>;
}

export const clientStore: ClientStore = {
    isLoading: false,

    update: async (data: ClientEditType) => {
        runInAction(() => {
            clientStore.isLoading = true;
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
                clientStore.isLoading = false;
            });
        }
    },

    delete: async () => {
        runInAction(() => {
            clientStore.isLoading = true;
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
                clientStore.isLoading = false;
            });
        }
    },

    activate: async (data: ClientActivateType) => {
        runInAction(() => {
            clientStore.isLoading = true;
        });

        try {
            const res = await apiPost<ApiItemResponse<ClientType>>('/me/activate/', data);

            runInAction(() => {
                authStore.user = res.data;
            });

        } catch (e) {
            console.error(e);
            throw e;
        } finally {
            runInAction(() => {
                clientStore.isLoading = false;
            });
        }
    }
};

makeAutoObservable(clientStore);