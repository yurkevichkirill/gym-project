import {
    cancelCurrentTrainerTraining,
    completeCurrentTrainerTraining,
    getCurrentTrainerTraining,
    getCurrentTrainerTrainings,
    getTrainerTrainingsRequestKey,
    updateCurrentTrainerTraining,
} from "@/api/trainer/training.api";
import { ApiClientError } from "@/lib/apiClient";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { authStore } from "@/store/AuthStore";
import {
    TrainerTrainingType,
    TrainerTrainingsGetParams,
    TrainerTrainingsListResponse,
    TrainerTrainingUpdatePayload,
} from "@/types/trainer/private/trainer-training.type";
import { makeAutoObservable, runInAction } from "mobx";

type TrainerTrainingPagination = TrainerTrainingsListResponse["meta"]["pagination"];

type ListTask = {
    generation: number;
    requestId: number;
    requestKey: string;
    promise: Promise<void>;
};

type DetailTask = {
    generation: number;
    requestId: number;
    trainingId: number;
    promise: Promise<void>;
};

type TrainerTrainingStorePrivateKey =
    | "generation"
    | "listRequestId"
    | "detailRequestId"
    | "currentParams"
    | "currentRequestKey"
    | "listTask"
    | "detailTask"
    | "updateTasks"
    | "cancelTasks"
    | "completeTasks";

const getErrorStatus = (error: unknown): number | null => {
    return error instanceof ApiClientError ? error.status : null;
};

class TrainerTrainingStore {
    public trainings: TrainerTrainingType[] = [];
    public pagination: TrainerTrainingPagination | null = null;
    public sort: Record<string, string> = {};
    public loadedRequestKey: string | null = null;
    public isLoading = false;
    public isRefreshing = false;
    public error: string | null = null;
    public errorStatus: number | null = null;

    public selectedTraining: TrainerTrainingType | null = null;
    public isDetailLoading = false;
    public detailError: string | null = null;
    public detailErrorStatus: number | null = null;

    public updatingIds: number[] = [];
    public cancelingIds: number[] = [];
    public completingIds: number[] = [];

    private generation = 0;
    private listRequestId = 0;
    private detailRequestId = 0;
    private currentParams: TrainerTrainingsGetParams = {};
    private currentRequestKey = "";
    private listTask: ListTask | null = null;
    private detailTask: DetailTask | null = null;
    private updateTasks = new Map<number, Promise<TrainerTrainingType>>();
    private cancelTasks = new Map<number, Promise<void>>();
    private completeTasks = new Map<number, Promise<TrainerTrainingType>>();

    public constructor() {
        makeAutoObservable<this, TrainerTrainingStorePrivateKey>(this, {
            generation: false,
            listRequestId: false,
            detailRequestId: false,
            currentParams: false,
            currentRequestKey: false,
            listTask: false,
            detailTask: false,
            updateTasks: false,
            cancelTasks: false,
            completeTasks: false,
        }, { autoBind: true });
    }

    public get isMutating(): boolean {
        return this.updatingIds.length > 0
            || this.cancelingIds.length > 0
            || this.completingIds.length > 0;
    }

    public init(params: TrainerTrainingsGetParams = {}): Promise<void> {
        if (!authStore.isAuth) {
            this.reset();
            return Promise.resolve();
        }

        const generation = this.generation;
        const requestKey = getTrainerTrainingsRequestKey(params);

        this.currentParams = { ...params };
        this.currentRequestKey = requestKey;

        if (
            this.listTask?.generation === generation
            && this.listTask.requestKey === requestKey
        ) {
            return this.listTask.promise;
        }

        const requestId = ++this.listRequestId;
        const promise = this.loadList(
            generation,
            requestId,
            params,
            requestKey,
        ).finally(() => {
            if (this.listTask?.requestId === requestId) {
                this.listTask = null;
            }
        });

        this.listTask = {
            generation,
            requestId,
            requestKey,
            promise,
        };

        return promise;
    }

    public loadTraining(trainingId: number): Promise<void> {
        if (!authStore.isAuth) {
            this.detailRequestId += 1;
            this.detailTask = null;
            this.resetDetail();
            return Promise.resolve();
        }

        const generation = this.generation;

        if (
            this.detailTask?.generation === generation
            && this.detailTask.trainingId === trainingId
        ) {
            return this.detailTask.promise;
        }

        const requestId = ++this.detailRequestId;
        const promise = this.loadDetail(
            generation,
            requestId,
            trainingId,
        ).finally(() => {
            if (this.detailTask?.requestId === requestId) {
                this.detailTask = null;
            }
        });

        this.detailTask = {
            generation,
            requestId,
            trainingId,
            promise,
        };

        return promise;
    }

    public update(
        id: number,
        payload: TrainerTrainingUpdatePayload,
    ): Promise<TrainerTrainingType> {
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
                    (trainingId) => trainingId !== id,
                );
            });
        });

        this.updateTasks.set(id, task);

        return task;
    }

    public cancel(id: number): Promise<void> {
        const existingTask = this.cancelTasks.get(id);

        if (existingTask) {
            return existingTask;
        }

        runInAction(() => {
            this.cancelingIds = [...this.cancelingIds, id];
        });

        const task = this.cancelInternal(id).finally(() => {
            this.cancelTasks.delete(id);

            runInAction(() => {
                this.cancelingIds = this.cancelingIds.filter(
                    (trainingId) => trainingId !== id,
                );
            });
        });

        this.cancelTasks.set(id, task);

        return task;
    }

    public complete(id: number): Promise<TrainerTrainingType> {
        const existingTask = this.completeTasks.get(id);

        if (existingTask) {
            return existingTask;
        }

        runInAction(() => {
            this.completingIds = [...this.completingIds, id];
        });

        const task = this.completeInternal(id).finally(() => {
            this.completeTasks.delete(id);

            runInAction(() => {
                this.completingIds = this.completingIds.filter(
                    (trainingId) => trainingId !== id,
                );
            });
        });

        this.completeTasks.set(id, task);

        return task;
    }

    public isUpdating(id: number): boolean {
        return this.updatingIds.includes(id);
    }

    public isCanceling(id: number): boolean {
        return this.cancelingIds.includes(id);
    }

    public isCompleting(id: number): boolean {
        return this.completingIds.includes(id);
    }

    public reset(): void {
        this.generation += 1;
        this.listRequestId += 1;
        this.detailRequestId += 1;
        this.currentParams = {};
        this.currentRequestKey = "";
        this.listTask = null;
        this.detailTask = null;
        this.updateTasks.clear();
        this.cancelTasks.clear();
        this.completeTasks.clear();
        this.trainings = [];
        this.pagination = null;
        this.sort = {};
        this.loadedRequestKey = null;
        this.isLoading = false;
        this.isRefreshing = false;
        this.error = null;
        this.errorStatus = null;
        this.updatingIds = [];
        this.cancelingIds = [];
        this.completingIds = [];
        this.resetDetail();
    }

    private async updateInternal(
        id: number,
        payload: TrainerTrainingUpdatePayload,
    ): Promise<TrainerTrainingType> {
        const generation = this.generation;

        try {
            const training = await updateCurrentTrainerTraining(id, payload);

            if (generation === this.generation && authStore.isAuth) {
                await this.syncAfterMutation(id);
            }

            return training;
        } catch (error: unknown) {
            await this.syncAfterConflict(error, id, generation);
            throw error;
        }
    }

    private async cancelInternal(id: number): Promise<void> {
        const generation = this.generation;

        try {
            await cancelCurrentTrainerTraining(id);

            if (generation === this.generation && authStore.isAuth) {
                await this.syncAfterMutation(id);
            }
        } catch (error: unknown) {
            await this.syncAfterConflict(error, id, generation);
            throw error;
        }
    }

    private async completeInternal(id: number): Promise<TrainerTrainingType> {
        const generation = this.generation;

        try {
            const training = await completeCurrentTrainerTraining(id);

            if (generation === this.generation && authStore.isAuth) {
                await this.syncAfterMutation(id);
            }

            return training;
        } catch (error: unknown) {
            await this.syncAfterConflict(error, id, generation);
            throw error;
        }
    }

    private async syncAfterConflict(
        error: unknown,
        id: number,
        generation: number,
    ): Promise<void> {
        if (
            generation !== this.generation
            || !authStore.isAuth
            || !(error instanceof ApiClientError)
            || (error.status !== 404 && error.status !== 409)
        ) {
            return;
        }

        await this.syncAfterMutation(id).catch(() => undefined);
    }

    private async syncAfterMutation(trainingId: number): Promise<void> {
        const shouldRefreshDetail = this.selectedTraining?.id === trainingId;
        const tasks: Promise<void>[] = [this.refreshList()];

        if (shouldRefreshDetail) {
            tasks.push(this.refreshDetail(trainingId));
        }

        await Promise.all(tasks);
    }

    private refreshList(): Promise<void> {
        this.listTask = null;

        return this.init(this.currentParams);
    }

    private refreshDetail(trainingId: number): Promise<void> {
        this.detailTask = null;

        return this.loadTraining(trainingId);
    }

    private async loadList(
        generation: number,
        requestId: number,
        params: TrainerTrainingsGetParams,
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
            const response = await getCurrentTrainerTrainings(params);

            if (
                generation === this.generation
                && requestId === this.listRequestId
                && requestKey === this.currentRequestKey
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.trainings = response.data;
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
                    this.error = getErrorMessage(error, "Failed to load trainer trainings.");
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

    private async loadDetail(
        generation: number,
        requestId: number,
        trainingId: number,
    ): Promise<void> {
        runInAction(() => {
            if (this.selectedTraining?.id !== trainingId) {
                this.selectedTraining = null;
            }

            this.isDetailLoading = true;
            this.detailError = null;
            this.detailErrorStatus = null;
        });

        try {
            const training = await getCurrentTrainerTraining(trainingId);

            if (
                generation === this.generation
                && requestId === this.detailRequestId
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.selectedTraining = training;
                });
            }
        } catch (error: unknown) {
            if (
                generation === this.generation
                && requestId === this.detailRequestId
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.detailError = getErrorMessage(
                        error,
                        "Failed to load trainer training details.",
                    );
                    this.detailErrorStatus = getErrorStatus(error);
                });
            }
        } finally {
            if (
                generation === this.generation
                && requestId === this.detailRequestId
            ) {
                runInAction(() => {
                    this.isDetailLoading = false;
                });
            }
        }
    }

    private resetDetail(): void {
        this.selectedTraining = null;
        this.isDetailLoading = false;
        this.detailError = null;
        this.detailErrorStatus = null;
    }
}

export const trainerTrainingStore = new TrainerTrainingStore();
