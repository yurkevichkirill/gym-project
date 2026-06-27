import { makeAutoObservable, runInAction } from "mobx";
import {
    deleteCurrentTrainer,
    getCurrentTrainer,
    updateCurrentTrainer,
    uploadCurrentTrainerPhoto,
} from "@/api/trainer/profile.api";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { authStore } from "@/store/AuthStore";
import TrainerEditType from "@/types/trainer/private/trainer-edit.type";
import { TrainerPersonalType } from "@/types/trainer/private/trainer.personal.type";

type LoadTask = {
    generation: number;
    requestId: number;
    promise: Promise<void>;
};

type TrainerStorePrivateKey =
    | "generation"
    | "loadRequestId"
    | "loadTask";

class TrainerStore {
    public trainer: TrainerPersonalType | null = null;
    public isLoading = false;
    public isUpdating = false;
    public isUploading = false;
    public isDeleting = false;
    public error: string | null = null;

    private generation = 0;
    private loadRequestId = 0;
    private loadTask: LoadTask | null = null;

    public constructor() {
        makeAutoObservable<this, TrainerStorePrivateKey>(this, {
            generation: false,
            loadRequestId: false,
            loadTask: false,
        }, { autoBind: true });
    }

    public get isMutating(): boolean {
        return this.isUpdating || this.isUploading || this.isDeleting;
    }

    public init(): Promise<void> {
        if (!authStore.isAuth) {
            this.reset();
            return Promise.resolve();
        }

        const generation = this.generation;
        if (this.loadTask?.generation === generation) {
            return this.loadTask.promise;
        }

        return this.startLoad(generation);
    }

    public reload(): Promise<void> {
        if (!authStore.isAuth) {
            this.reset();
            return Promise.resolve();
        }

        this.loadTask = null;

        return this.startLoad(this.generation, true);
    }

    public async update(payload: TrainerEditType): Promise<void> {
        const generation = this.generation;

        runInAction(() => {
            this.isUpdating = true;
            this.error = null;
        });

        try {
            await updateCurrentTrainer(payload);

            if (generation !== this.generation || !authStore.isAuth) {
                return;
            }

            await this.reload();
        } catch (error: unknown) {
            if (generation === this.generation && authStore.isAuth) {
                runInAction(() => {
                    this.error = getErrorMessage(error, "Failed to update the trainer profile.");
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

    public async uploadPhoto(photo: File): Promise<void> {
        const generation = this.generation;

        runInAction(() => {
            this.isUploading = true;
            this.error = null;
        });

        try {
            await uploadCurrentTrainerPhoto(photo);

            if (generation !== this.generation || !authStore.isAuth) {
                return;
            }

            await this.reload();
        } catch (error: unknown) {
            if (generation === this.generation && authStore.isAuth) {
                runInAction(() => {
                    this.error = getErrorMessage(error, "Failed to upload the trainer photo.");
                });
            }

            throw error;
        } finally {
            if (generation === this.generation) {
                runInAction(() => {
                    this.isUploading = false;
                });
            }
        }
    }

    public async delete(): Promise<void> {
        const generation = this.generation;

        runInAction(() => {
            this.isDeleting = true;
            this.error = null;
        });

        try {
            await deleteCurrentTrainer();

            if (generation === this.generation) {
                void authStore.logout().catch(() => undefined);
            }
        } catch (error: unknown) {
            if (generation === this.generation && authStore.isAuth) {
                runInAction(() => {
                    this.error = getErrorMessage(error, "Failed to delete the trainer profile.");
                });
            }

            throw error;
        } finally {
            if (generation === this.generation) {
                runInAction(() => {
                    this.isDeleting = false;
                });
            }
        }
    }

    public reset(): void {
        this.generation += 1;
        this.loadRequestId += 1;
        this.loadTask = null;
        this.trainer = null;
        this.isLoading = false;
        this.isUpdating = false;
        this.isUploading = false;
        this.isDeleting = false;
        this.error = null;
    }

    private startLoad(
        generation: number,
        rethrowError = false,
    ): Promise<void> {
        const requestId = ++this.loadRequestId;
        const promise = this.load(generation, requestId, rethrowError).finally(() => {
            if (this.loadTask?.requestId === requestId) {
                this.loadTask = null;
            }
        });

        this.loadTask = {
            generation,
            requestId,
            promise,
        };

        return promise;
    }

    private async load(
        generation: number,
        requestId: number,
        rethrowError: boolean,
    ): Promise<void> {
        runInAction(() => {
            this.isLoading = true;
            this.error = null;
        });

        try {
            const trainer = await getCurrentTrainer();

            if (
                generation !== this.generation
                || requestId !== this.loadRequestId
                || !authStore.isAuth
            ) {
                return;
            }

            runInAction(() => {
                this.trainer = trainer;
            });
        } catch (error: unknown) {
            const isCurrentRequest = generation === this.generation
                && requestId === this.loadRequestId
                && authStore.isAuth;

            if (isCurrentRequest) {
                runInAction(() => {
                    if (!rethrowError) {
                        this.trainer = null;
                    }

                    this.error = getErrorMessage(error, "Failed to load the trainer profile.");
                });
            }

            if (rethrowError && isCurrentRequest) {
                throw error;
            }
        } finally {
            if (
                generation === this.generation
                && requestId === this.loadRequestId
            ) {
                runInAction(() => {
                    this.isLoading = false;
                });
            }
        }
    }
}

export const trainerStore = new TrainerStore();
