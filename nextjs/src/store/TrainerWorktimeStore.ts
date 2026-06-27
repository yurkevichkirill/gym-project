import {
    createCurrentTrainerWorktime,
    deleteCurrentTrainerWorktime,
    getCurrentTrainerWorktimes,
    getTrainerWorktimesRequestKey,
    updateCurrentTrainerWorktime,
} from "@/api/trainer/worktime.api";
import { ApiClientError } from "@/lib/apiClient";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { authStore } from "@/store/AuthStore";
import {
    TrainerWorktimeCreatePayload,
    TrainerWorktimesGetParams,
    TrainerWorktimesListResponse,
    TrainerWorktimeUpdatePayload,
} from "@/types/trainer/private/trainer-worktime.type";
import WorktimeData from "@/types/trainer/public/worktime.type";
import { makeAutoObservable, runInAction } from "mobx";

type TrainerWorktimePagination = TrainerWorktimesListResponse["meta"]["pagination"];

type InitTask = {
    generation: number;
    requestId: number;
    requestKey: string;
    promise: Promise<void>;
};

type TrainerWorktimeStorePrivateKey =
    | "generation"
    | "listRequestId"
    | "currentParams"
    | "currentRequestKey"
    | "initTask"
    | "updateTasks"
    | "deleteTasks";

const getErrorStatus = (error: unknown): number | null => {
    return error instanceof ApiClientError ? error.status : null;
};

class TrainerWorktimeStore {
    public worktimes: WorktimeData[] = [];
    public pagination: TrainerWorktimePagination | null = null;
    public sort: Record<string, string> = {};
    public loadedRequestKey: string | null = null;
    public isLoading = false;
    public isRefreshing = false;
    public error: string | null = null;
    public errorStatus: number | null = null;
    public isCreating = false;
    public updatingIds: number[] = [];
    public deletingIds: number[] = [];

    private generation = 0;
    private listRequestId = 0;
    private currentParams: TrainerWorktimesGetParams = {};
    private currentRequestKey = "";
    private initTask: InitTask | null = null;
    private updateTasks = new Map<number, Promise<WorktimeData>>();
    private deleteTasks = new Map<number, Promise<void>>();

    public constructor() {
        makeAutoObservable<this, TrainerWorktimeStorePrivateKey>(this, {
            generation: false,
            listRequestId: false,
            currentParams: false,
            currentRequestKey: false,
            initTask: false,
            updateTasks: false,
            deleteTasks: false,
        }, { autoBind: true });
    }

    public get isMutating(): boolean {
        return this.isCreating
            || this.updatingIds.length > 0
            || this.deletingIds.length > 0;
    }

    public init(params: TrainerWorktimesGetParams = {}): Promise<void> {
        if (!authStore.isAuth) {
            this.reset();
            return Promise.resolve();
        }

        const generation = this.generation;
        const requestKey = getTrainerWorktimesRequestKey(params);

        this.currentParams = { ...params };
        this.currentRequestKey = requestKey;

        if (
            this.initTask?.generation === generation
            && this.initTask.requestKey === requestKey
        ) {
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

    public reload(): Promise<void> {
        this.initTask = null;

        return this.init(this.currentParams);
    }

    public async create(payload: TrainerWorktimeCreatePayload): Promise<WorktimeData> {
        if (this.isCreating) {
            throw new Error("A worktime creation request is already in progress.");
        }

        const generation = this.generation;

        runInAction(() => {
            this.isCreating = true;
        });

        try {
            const worktime = await createCurrentTrainerWorktime(payload);

            if (generation === this.generation && authStore.isAuth) {
                await this.reload();
            }

            return worktime;
        } finally {
            if (generation === this.generation) {
                runInAction(() => {
                    this.isCreating = false;
                });
            }
        }
    }

    public update(
        id: number,
        payload: TrainerWorktimeUpdatePayload,
    ): Promise<WorktimeData> {
        const existingTask = this.updateTasks.get(id);

        if (existingTask) {
            return existingTask;
        }

        runInAction(() => {
            this.updatingIds = [...this.updatingIds, id];
        });

        const task = this.updateInternal(id, payload).finally(() => {
            this.updateTasks.delete(id);

            runInAction(() => {
                this.updatingIds = this.updatingIds.filter(
                    (worktimeId) => worktimeId !== id,
                );
            });
        });

        this.updateTasks.set(id, task);

        return task;
    }

    public remove(id: number): Promise<void> {
        const existingTask = this.deleteTasks.get(id);

        if (existingTask) {
            return existingTask;
        }

        runInAction(() => {
            this.deletingIds = [...this.deletingIds, id];
        });

        const task = this.removeInternal(id).finally(() => {
            this.deleteTasks.delete(id);

            runInAction(() => {
                this.deletingIds = this.deletingIds.filter(
                    (worktimeId) => worktimeId !== id,
                );
            });
        });

        this.deleteTasks.set(id, task);

        return task;
    }

    public isUpdating(id: number): boolean {
        return this.updatingIds.includes(id);
    }

    public isDeleting(id: number): boolean {
        return this.deletingIds.includes(id);
    }

    public reset(): void {
        this.generation += 1;
        this.listRequestId += 1;
        this.currentParams = {};
        this.currentRequestKey = "";
        this.initTask = null;
        this.updateTasks.clear();
        this.deleteTasks.clear();
        this.worktimes = [];
        this.pagination = null;
        this.sort = {};
        this.loadedRequestKey = null;
        this.isLoading = false;
        this.isRefreshing = false;
        this.error = null;
        this.errorStatus = null;
        this.isCreating = false;
        this.updatingIds = [];
        this.deletingIds = [];
    }

    private async updateInternal(
        id: number,
        payload: TrainerWorktimeUpdatePayload,
    ): Promise<WorktimeData> {
        const generation = this.generation;
        const worktime = await updateCurrentTrainerWorktime(id, payload);

        if (generation === this.generation && authStore.isAuth) {
            await this.reload();
        }

        return worktime;
    }

    private async removeInternal(id: number): Promise<void> {
        const generation = this.generation;

        await deleteCurrentTrainerWorktime(id);

        if (generation === this.generation && authStore.isAuth) {
            await this.reload();
        }
    }

    private async load(
        generation: number,
        requestId: number,
        params: TrainerWorktimesGetParams,
        requestKey: string,
    ): Promise<void> {
        const hasExistingResponse = this.loadedRequestKey !== null;

        runInAction(() => {
            this.isLoading = !hasExistingResponse;
            this.isRefreshing = hasExistingResponse;
            this.error = null;
            this.errorStatus = null;
        });

        try {
            const response = await getCurrentTrainerWorktimes(params);

            if (
                generation === this.generation
                && requestId === this.listRequestId
                && requestKey === this.currentRequestKey
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.worktimes = response.data;
                    this.pagination = response.meta.pagination;
                    this.sort = response.meta.sort ?? {};
                    this.loadedRequestKey = requestKey;
                });
            }
        } catch (error: unknown) {
            if (
                generation === this.generation
                && requestId === this.listRequestId
                && requestKey === this.currentRequestKey
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.error = getErrorMessage(error, "Failed to load trainer worktimes.");
                    this.errorStatus = getErrorStatus(error);
                });
            }
        } finally {
            if (
                generation === this.generation
                && requestId === this.listRequestId
                && requestKey === this.currentRequestKey
            ) {
                runInAction(() => {
                    this.isLoading = false;
                    this.isRefreshing = false;
                });
            }
        }
    }
}

export const trainerWorktimeStore = new TrainerWorktimeStore();
