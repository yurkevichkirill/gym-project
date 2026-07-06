import { action, makeObservable, observable, runInAction } from "mobx";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { getAdminErrorMessage, getApiErrorStatus } from "@/api/admin/admin-api-utils";

type Pagination<T> = ApiCollectionResponse<T[]>["meta"]["pagination"];

type ListTask = {
    requestId: number;
    requestKey: string;
    promise: Promise<void>;
};

type DetailTask = {
    requestId: number;
    id: number;
    promise: Promise<void>;
};

export class AdminResourceStore<TItem extends { id: number }, TParams extends object> {
    public items: TItem[] = [];
    public pagination: Pagination<TItem> | null = null;
    public loadedRequestKey: string | null = null;
    public isLoading = false;
    public isRefreshing = false;
    public error: string | null = null;
    public errorStatus: number | null = null;

    public selected: TItem | null = null;
    public isDetailLoading = false;
    public detailError: string | null = null;
    public detailErrorStatus: number | null = null;

    public mutationError: string | null = null;
    public actionKeys: string[] = [];

    private listRequestId = 0;
    private detailRequestId = 0;
    private currentParams: TParams = {} as TParams;
    private currentRequestKey = "";
    private listTask: ListTask | null = null;
    private detailTask: DetailTask | null = null;

    public constructor(
        private readonly getList: (params: TParams) => Promise<ApiCollectionResponse<TItem[]>>,
        private readonly getRequestKey: (params: TParams) => string,
        private readonly getDetail?: (id: number) => Promise<TItem>,
    ) {
        makeObservable<this, "listRequestId" | "detailRequestId" | "currentParams" | "currentRequestKey" | "listTask" | "detailTask">(this, {
            items: observable,
            pagination: observable,
            loadedRequestKey: observable,
            isLoading: observable,
            isRefreshing: observable,
            error: observable,
            errorStatus: observable,
            selected: observable,
            isDetailLoading: observable,
            detailError: observable,
            detailErrorStatus: observable,
            mutationError: observable,
            actionKeys: observable,
            listRequestId: false,
            detailRequestId: false,
            currentParams: false,
            currentRequestKey: false,
            listTask: false,
            detailTask: false,
            init: action.bound,
            loadDetail: action.bound,
            applyItem: action.bound,
            removeItem: action.bound,
            isActionRunning: action.bound,
            runAction: action.bound,
            refetch: action.bound,
            reset: action.bound,
        });
    }

    public init(params: TParams): Promise<void> {
        const requestKey = this.getRequestKey(params);
        this.currentParams = { ...params };
        this.currentRequestKey = requestKey;

        if (this.listTask?.requestKey === requestKey) {
            return this.listTask.promise;
        }

        const requestId = ++this.listRequestId;
        const promise = this.load(requestId, params, requestKey).finally(() => {
            if (this.listTask?.requestId === requestId) {
                this.listTask = null;
            }
        });

        this.listTask = { requestId, requestKey, promise };

        return promise;
    }

    public loadDetail(id: number): Promise<void> {
        if (this.getDetail === undefined) {
            return Promise.resolve();
        }

        if (this.detailTask?.id === id) {
            return this.detailTask.promise;
        }

        const requestId = ++this.detailRequestId;
        const promise = this.loadSelected(requestId, id).finally(() => {
            if (this.detailTask?.requestId === requestId) {
                this.detailTask = null;
            }
        });

        this.detailTask = { requestId, id, promise };

        return promise;
    }

    public applyItem(item: TItem): void {
        const index = this.items.findIndex((entry) => entry.id === item.id);

        runInAction(() => {
            if (index >= 0) {
                this.items[index] = item;
            }

            if (this.selected?.id === item.id) {
                this.selected = item;
            }
        });
    }

    public removeItem(id: number): void {
        runInAction(() => {
            this.items = this.items.filter((entry) => entry.id !== id);

            if (this.selected?.id === id) {
                this.selected = null;
            }
        });
    }

    public isActionRunning(id: number, action: string): boolean {
        return this.actionKeys.includes(this.getActionKey(id, action));
    }

    public async runAction(
        id: number,
        action: string,
        callback: () => Promise<TItem | void>,
        fallback: string,
    ): Promise<TItem | void> {
        const key = this.getActionKey(id, action);

        if (this.actionKeys.includes(key)) {
            return undefined;
        }

        runInAction(() => {
            this.actionKeys = [...this.actionKeys, key];
            this.mutationError = null;
        });

        try {
            const result = await callback();

            if (result !== undefined) {
                this.applyItem(result);
            } else {
                await this.init(this.currentParams);
            }

            return result;
        } catch (error: unknown) {
            runInAction(() => {
                this.mutationError = getAdminErrorMessage(error, fallback);
            });

            throw error;
        } finally {
            runInAction(() => {
                this.actionKeys = this.actionKeys.filter((entry) => entry !== key);
            });
        }
    }

    public async refetch(): Promise<void> {
        await this.init(this.currentParams);
    }

    public reset(): void {
        this.listRequestId += 1;
        this.detailRequestId += 1;
        this.items = [];
        this.pagination = null;
        this.loadedRequestKey = null;
        this.isLoading = false;
        this.isRefreshing = false;
        this.error = null;
        this.errorStatus = null;
        this.selected = null;
        this.isDetailLoading = false;
        this.detailError = null;
        this.detailErrorStatus = null;
        this.mutationError = null;
        this.actionKeys = [];
        this.currentParams = {} as TParams;
        this.currentRequestKey = "";
        this.listTask = null;
        this.detailTask = null;
    }

    private async load(requestId: number, params: TParams, requestKey: string): Promise<void> {
        const hasLoadedData = this.loadedRequestKey !== null;

        runInAction(() => {
            this.isLoading = !hasLoadedData;
            this.isRefreshing = hasLoadedData;
            this.error = null;
            this.errorStatus = null;
        });

        try {
            const response = await this.getList(params);

            if (requestId === this.listRequestId) {
                runInAction(() => {
                    this.items = response.data;
                    this.pagination = response.meta.pagination;
                    this.loadedRequestKey = requestKey;
                    this.isLoading = false;
                    this.isRefreshing = false;
                });
            }
        } catch (error: unknown) {
            if (requestId === this.listRequestId) {
                runInAction(() => {
                    this.error = getAdminErrorMessage(error, "Failed to load records.");
                    this.errorStatus = getApiErrorStatus(error);
                    this.isLoading = false;
                    this.isRefreshing = false;
                });
            }
        }
    }

    private async loadSelected(requestId: number, id: number): Promise<void> {
        if (this.getDetail === undefined) {
            return;
        }

        runInAction(() => {
            this.isDetailLoading = true;
            this.detailError = null;
            this.detailErrorStatus = null;
        });

        try {
            const response = await this.getDetail(id);

            if (requestId === this.detailRequestId) {
                runInAction(() => {
                    this.selected = response;
                    this.isDetailLoading = false;
                });
            }
        } catch (error: unknown) {
            if (requestId === this.detailRequestId) {
                runInAction(() => {
                    this.detailError = getAdminErrorMessage(error, "Failed to load record.");
                    this.detailErrorStatus = getApiErrorStatus(error);
                    this.isDetailLoading = false;
                });
            }
        }
    }

    private getActionKey(id: number, action: string): string {
        return `${id}:${action}`;
    }
}

