import {makeAutoObservable, runInAction} from "mobx";
import {authStore} from "@/store/AuthStore";
import ClientEditType from "@/types/client/client-edit.type";
import {apiPost} from "@/lib/apiClient";
import {ApiItemResponse} from "@/types/api-item-response.type";
import ClientActivateType from "@/types/client/clientActivate.type";
import ClientType from "@/types/client/client.type";
import {
    deleteCurrentClient,
    getCurrentClient,
    updateCurrentClient,
} from "@/api/client/profile.api";
import {getErrorMessage} from "@/lib/getErrorMessage";

type InitTask = {
    generation: number;
    promise: Promise<void>;
};

class ClientStore {
    public client: ClientType | null = null;
    public isLoading = false;
    public error: string | null = null;

    private generation = 0;
    private initTask: InitTask | null = null;

    public constructor() {
        makeAutoObservable(this, {
            generation: false,
            initTask: false,
        }, {autoBind: true});
    }

    public init(): Promise<void> {
        if (!authStore.isAuth) {
            this.reset();
            return Promise.resolve();
        }

        const generation = this.generation;
        if (this.initTask?.generation === generation) {
            return this.initTask.promise;
        }

        const promise = this.load(generation).finally(() => {
            if (this.initTask?.promise === promise) {
                this.initTask = null;
            }
        });

        this.initTask = {generation, promise};

        return promise;
    }

    public async update(payload: ClientEditType): Promise<void> {
        const generation = this.generation;

        runInAction(() => {
            this.isLoading = true;
            this.error = null;
        });

        try {
            const client = await updateCurrentClient(payload);

            if (generation !== this.generation || !authStore.isAuth) {
                return;
            }

            runInAction(() => {
                this.client = client;
            });
        } catch (error: unknown) {
            if (generation === this.generation && authStore.isAuth) {
                runInAction(() => {
                    this.error = getErrorMessage(error, "Failed to update the profile.");
                });
            }

            throw error;
        } finally {
            if (generation === this.generation) {
                runInAction(() => {
                    this.isLoading = false;
                });
            }
        }
    }

    public async delete(): Promise<void> {
        const generation = this.generation;

        runInAction(() => {
            this.isLoading = true;
            this.error = null;
        });

        try {
            await deleteCurrentClient();

            if (generation === this.generation) {
                await authStore.logout();
            }
        } catch (error: unknown) {
            if (generation === this.generation && authStore.isAuth) {
                runInAction(() => {
                    this.error = getErrorMessage(error, "Failed to delete the profile.");
                });
            }

            throw error;
        } finally {
            if (generation === this.generation) {
                runInAction(() => {
                    this.isLoading = false;
                });
            }
        }
    }

    public async activate(payload: ClientActivateType): Promise<void> {
        const generation = this.generation;

        runInAction(() => {
            this.isLoading = true;
            this.error = null;
        });

        try {
            const response = await apiPost<ApiItemResponse<ClientType>, ClientActivateType>(
                "/clients/activate/",
                payload,
                {skipAuthRefresh: true},
            );

            if (generation === this.generation) {
                runInAction(() => {
                    this.client = response.data;
                });
            }
        } catch (error: unknown) {
            if (generation === this.generation) {
                runInAction(() => {
                    this.error = getErrorMessage(error, "Failed to activate the profile.");
                });
            }

            throw error;
        } finally {
            if (generation === this.generation) {
                runInAction(() => {
                    this.isLoading = false;
                });
            }
        }
    }

    public reset(): void {
        this.generation += 1;
        this.initTask = null;
        this.client = null;
        this.isLoading = false;
        this.error = null;
    }

    private async load(generation: number): Promise<void> {
        runInAction(() => {
            this.isLoading = true;
            this.error = null;
        });

        try {
            const client = await getCurrentClient();

            if (generation !== this.generation || !authStore.isAuth) {
                return;
            }

            runInAction(() => {
                this.client = client;
            });
        } catch (error: unknown) {
            if (generation === this.generation && authStore.isAuth) {
                runInAction(() => {
                    this.client = null;
                    this.error = getErrorMessage(error, "Failed to load the profile.");
                });
            }
        } finally {
            if (generation === this.generation) {
                runInAction(() => {
                    this.isLoading = false;
                });
            }
        }
    }
}

export const clientStore = new ClientStore();
