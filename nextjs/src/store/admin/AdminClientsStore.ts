import { makeAutoObservable, runInAction } from "mobx";
import { ApiClientError } from "@/lib/apiClient";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import MembershipType from "@/types/membership/membership.type";
import {
    blockAdminClient,
    createAdminClient,
    deleteAdminClient,
    getAdminClient,
    getAdminClients,
    getAdminClientsRequestKey,
    importAdminClients,
    registerAdminClientVisit,
    restoreAdminClient,
    unblockAdminClient,
    updateAdminClient,
} from "@/api/admin/clients.api";
import type {
    AdminClient,
    AdminClientCreateRequest,
    AdminClientImportRequest,
    AdminClientImportResponse,
    AdminClientsGetQueryParams,
    AdminClientUpdateRequest,
} from "@/types/admin/admin-client.type";

type Pagination = ApiCollectionResponse<AdminClient[]>["meta"]["pagination"];

type InitTask = {
    generation: number;
    requestId: number;
    requestKey: string;
    promise: Promise<void>;
};

type DetailTask = {
    generation: number;
    requestId: number;
    clientId: number;
    promise: Promise<void>;
};

type AdminClientAction = "delete" | "restore" | "block" | "unblock" | "visit";

type AdminClientsStorePrivateKey =
    | "generation"
    | "listRequestId"
    | "detailRequestId"
    | "currentParams"
    | "currentRequestKey"
    | "initTask"
    | "detailTask"
    | "actionTasks";

const getErrorStatus = (error: unknown): number | null => {
    return error instanceof ApiClientError ? error.status : null;
};

const getMutationErrorMessage = (error: unknown, fallback: string): string => {
    if (error instanceof ApiClientError) {
        if (error.status === 409) {
            return error.payload.message || "The requested action conflicts with the current client state.";
        }

        if (error.status === 422 || error.status === 400) {
            return error.payload.message || "Please check the submitted values and try again.";
        }

        if (error.status === 403) {
            return "Your account is not allowed to perform this action.";
        }

        if (error.status === 404) {
            return "The client no longer exists.";
        }
    }

    return getErrorMessage(error, fallback);
};

class AdminClientsStore {
    public clients: AdminClient[] = [];
    public pagination: Pagination | null = null;
    public sort: Record<string, string> = {};
    public loadedRequestKey: string | null = null;
    public isLoading = false;
    public isRefreshing = false;
    public error: string | null = null;
    public errorStatus: number | null = null;

    public selectedClient: AdminClient | null = null;
    public isDetailLoading = false;
    public detailError: string | null = null;
    public detailErrorStatus: number | null = null;

    public isCreating = false;
    public isUpdating = false;
    public isImporting = false;
    public importResult: AdminClientImportResponse | null = null;
    public mutationError: string | null = null;
    public actionKeys: string[] = [];

    private generation = 0;
    private listRequestId = 0;
    private detailRequestId = 0;
    private currentParams: AdminClientsGetQueryParams = {};
    private currentRequestKey = "";
    private initTask: InitTask | null = null;
    private detailTask: DetailTask | null = null;
    private actionTasks = new Map<string, Promise<unknown>>();

    public constructor() {
        makeAutoObservable<this, AdminClientsStorePrivateKey>(this, {
            generation: false,
            listRequestId: false,
            detailRequestId: false,
            currentParams: false,
            currentRequestKey: false,
            initTask: false,
            detailTask: false,
            actionTasks: false,
        }, { autoBind: true });
    }

    public init(params: AdminClientsGetQueryParams = {}): Promise<void> {
        const generation = this.generation;
        const requestKey = getAdminClientsRequestKey(params);

        this.currentParams = { ...params };
        this.currentRequestKey = requestKey;

        if (this.initTask?.generation === generation && this.initTask.requestKey === requestKey) {
            return this.initTask.promise;
        }

        const requestId = ++this.listRequestId;
        const promise = this.load(generation, requestId, params, requestKey).finally(() => {
            if (this.initTask?.requestId === requestId) {
                this.initTask = null;
            }
        });

        this.initTask = { generation, requestId, requestKey, promise };

        return promise;
    }

    public loadClient(clientId: number): Promise<void> {
        const generation = this.generation;

        if (this.detailTask?.generation === generation && this.detailTask.clientId === clientId) {
            return this.detailTask.promise;
        }

        const requestId = ++this.detailRequestId;
        const promise = this.loadDetail(generation, requestId, clientId).finally(() => {
            if (this.detailTask?.requestId === requestId) {
                this.detailTask = null;
            }
        });

        this.detailTask = { generation, requestId, clientId, promise };

        return promise;
    }

    public isActionRunning(clientId: number, action: AdminClientAction): boolean {
        return this.actionKeys.includes(this.getActionKey(clientId, action));
    }

    public async create(payload: AdminClientCreateRequest): Promise<AdminClient> {
        if (this.isCreating) {
            throw new Error("A client creation request is already in progress.");
        }

        const generation = this.generation;

        runInAction(() => {
            this.isCreating = true;
            this.mutationError = null;
        });

        try {
            const client = await createAdminClient(payload);

            if (generation === this.generation) {
                await this.syncAfterMutation(client.id);
            }

            return client;
        } catch (error: unknown) {
            if (generation === this.generation) {
                runInAction(() => {
                    this.mutationError = getMutationErrorMessage(error, "Failed to create client.");
                });
            }

            throw error;
        } finally {
            if (generation === this.generation) {
                runInAction(() => {
                    this.isCreating = false;
                });
            }
        }
    }

    public async update(clientId: number, payload: AdminClientUpdateRequest): Promise<AdminClient> {
        if (this.isUpdating) {
            throw new Error("A client update request is already in progress.");
        }

        const generation = this.generation;

        runInAction(() => {
            this.isUpdating = true;
            this.mutationError = null;
        });

        try {
            const client = await updateAdminClient(clientId, payload);

            if (generation === this.generation) {
                this.applyClient(client);
            }

            return client;
        } catch (error: unknown) {
            if (generation === this.generation) {
                runInAction(() => {
                    this.mutationError = getMutationErrorMessage(error, "Failed to update client.");
                });
            }

            throw error;
        } finally {
            if (generation === this.generation) {
                runInAction(() => {
                    this.isUpdating = false;
                });
            }
        }
    }

    public delete(clientId: number): Promise<void> {
        return this.runAction(clientId, "delete", async () => {
            await deleteAdminClient(clientId);
            await this.syncAfterMutation(clientId);
        }) as Promise<void>;
    }

    public restore(clientId: number): Promise<AdminClient> {
        return this.runAction(clientId, "restore", async () => {
            const client = await restoreAdminClient(clientId);
            this.applyClient(client);
            await this.syncList();
            return client;
        }) as Promise<AdminClient>;
    }

    public block(clientId: number): Promise<AdminClient> {
        return this.runAction(clientId, "block", async () => {
            const client = await blockAdminClient(clientId);
            this.applyClient(client);
            return client;
        }) as Promise<AdminClient>;
    }

    public unblock(clientId: number): Promise<AdminClient> {
        return this.runAction(clientId, "unblock", async () => {
            const client = await unblockAdminClient(clientId);
            this.applyClient(client);
            return client;
        }) as Promise<AdminClient>;
    }

    public visit(clientId: number): Promise<MembershipType> {
        return this.runAction(clientId, "visit", async () => {
            return await registerAdminClientVisit(clientId);
        }) as Promise<MembershipType>;
    }

    public async import(payload: AdminClientImportRequest): Promise<AdminClientImportResponse> {
        if (this.isImporting) {
            throw new Error("A client import request is already in progress.");
        }

        const generation = this.generation;

        runInAction(() => {
            this.isImporting = true;
            this.mutationError = null;
            this.importResult = null;
        });

        try {
            const result = await importAdminClients(payload);

            if (generation === this.generation) {
                runInAction(() => {
                    this.importResult = result;
                });

                await this.syncList();
            }

            return result;
        } catch (error: unknown) {
            if (generation === this.generation) {
                runInAction(() => {
                    this.mutationError = getMutationErrorMessage(error, "Failed to queue client import.");
                });
            }

            throw error;
        } finally {
            if (generation === this.generation) {
                runInAction(() => {
                    this.isImporting = false;
                });
            }
        }
    }

    public reset(): void {
        this.generation += 1;
        this.listRequestId += 1;
        this.detailRequestId += 1;
        this.initTask = null;
        this.detailTask = null;
        this.actionTasks.clear();
        this.clients = [];
        this.pagination = null;
        this.sort = {};
        this.loadedRequestKey = null;
        this.isLoading = false;
        this.isRefreshing = false;
        this.error = null;
        this.errorStatus = null;
        this.selectedClient = null;
        this.isDetailLoading = false;
        this.detailError = null;
        this.detailErrorStatus = null;
        this.isCreating = false;
        this.isUpdating = false;
        this.isImporting = false;
        this.importResult = null;
        this.mutationError = null;
        this.actionKeys = [];
    }

    private async load(
        generation: number,
        requestId: number,
        params: AdminClientsGetQueryParams,
        requestKey: string,
    ): Promise<void> {
        runInAction(() => {
            if (this.loadedRequestKey === null) {
                this.isLoading = true;
            } else {
                this.isRefreshing = true;
            }
            this.error = null;
            this.errorStatus = null;
        });

        try {
            const response = await getAdminClients(params);

            if (generation === this.generation && requestId === this.listRequestId) {
                runInAction(() => {
                    this.clients = response.data;
                    this.pagination = response.meta.pagination;
                    this.sort = response.meta.sort ?? {};
                    this.loadedRequestKey = requestKey;
                });
            }
        } catch (error: unknown) {
            if (generation === this.generation && requestId === this.listRequestId) {
                runInAction(() => {
                    this.error = getErrorMessage(error, "Failed to load clients.");
                    this.errorStatus = getErrorStatus(error);
                });
            }
        } finally {
            if (generation === this.generation && requestId === this.listRequestId) {
                runInAction(() => {
                    this.isLoading = false;
                    this.isRefreshing = false;
                });
            }
        }
    }

    private async loadDetail(generation: number, requestId: number, clientId: number): Promise<void> {
        runInAction(() => {
            if (this.selectedClient?.id !== clientId) {
                this.selectedClient = null;
            }
            this.isDetailLoading = true;
            this.detailError = null;
            this.detailErrorStatus = null;
        });

        try {
            const client = await getAdminClient(clientId);

            if (generation === this.generation && requestId === this.detailRequestId) {
                runInAction(() => {
                    this.applyClient(client);
                });
            }
        } catch (error: unknown) {
            if (generation === this.generation && requestId === this.detailRequestId) {
                runInAction(() => {
                    this.detailError = getErrorMessage(error, "Failed to load client.");
                    this.detailErrorStatus = getErrorStatus(error);
                });
            }
        } finally {
            if (generation === this.generation && requestId === this.detailRequestId) {
                runInAction(() => {
                    this.isDetailLoading = false;
                });
            }
        }
    }

    private runAction(clientId: number, action: AdminClientAction, handler: () => Promise<unknown>): Promise<unknown> {
        const key = this.getActionKey(clientId, action);
        const existingTask = this.actionTasks.get(key);

        if (existingTask !== undefined) {
            return existingTask;
        }

        runInAction(() => {
            this.actionKeys = [...this.actionKeys, key];
            this.mutationError = null;
        });

        const task = handler().catch((error: unknown) => {
            runInAction(() => {
                this.mutationError = getMutationErrorMessage(error, `Failed to ${action} client.`);
            });

            throw error;
        }).finally(() => {
            this.actionTasks.delete(key);
            runInAction(() => {
                this.actionKeys = this.actionKeys.filter((item) => item !== key);
            });
        });

        this.actionTasks.set(key, task);

        return task;
    }

    private async syncAfterMutation(detailClientId?: number): Promise<void> {
        await this.syncList();

        if (detailClientId !== undefined) {
            this.detailTask = null;
            await this.loadClient(detailClientId);
        }
    }

    private async syncList(): Promise<void> {
        this.initTask = null;
        await this.init(this.currentParams);
    }

    private applyClient(client: AdminClient): void {
        this.selectedClient = client;
        this.clients = this.clients.map((item) => item.id === client.id ? client : item);
    }

    private getActionKey(clientId: number, action: AdminClientAction): string {
        return `${clientId}:${action}`;
    }
}

export const adminClientsStore = new AdminClientsStore();
