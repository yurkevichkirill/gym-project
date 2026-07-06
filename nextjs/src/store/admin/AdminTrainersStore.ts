import { action, makeObservable, observable, runInAction } from "mobx";
import {
    blockAdminTrainer,
    createAdminTrainer,
    deleteAdminTrainer,
    getAdminTrainer,
    getAdminTrainers,
    getAdminTrainersRequestKey,
    restoreAdminTrainer,
    unblockAdminTrainer,
    updateAdminTrainer,
    uploadAdminTrainerPhoto,
} from "@/api/admin/trainers.api";
import { AdminResourceStore } from "@/store/admin/createAdminResourceStore";
import type {
    AdminTrainer,
    AdminTrainerCreateRequest,
    AdminTrainersGetQueryParams,
    AdminTrainerUpdateRequest,
} from "@/types/admin/admin-trainer.type";
import { getAdminErrorMessage } from "@/api/admin/admin-api-utils";

class AdminTrainersStore extends AdminResourceStore<AdminTrainer, AdminTrainersGetQueryParams> {
    public isCreating = false;
    public isUpdating = false;
    public isUploadingPhoto = false;

    public constructor() {
        super(getAdminTrainers, getAdminTrainersRequestKey, getAdminTrainer);
        makeObservable(this, {
            isCreating: observable,
            isUpdating: observable,
            isUploadingPhoto: observable,
            create: action.bound,
            update: action.bound,
            uploadPhoto: action.bound,
            delete: action.bound,
            restore: action.bound,
            block: action.bound,
            unblock: action.bound,
        });
    }

    public async create(payload: AdminTrainerCreateRequest): Promise<AdminTrainer> {
        if (this.isCreating) {
            throw new Error("A trainer creation request is already in progress.");
        }

        runInAction(() => {
            this.isCreating = true;
            this.mutationError = null;
        });

        try {
            const trainer = await createAdminTrainer(payload);
            await this.refetch();
            return trainer;
        } catch (error: unknown) {
            runInAction(() => {
                this.mutationError = getAdminErrorMessage(error, "Failed to create trainer.");
            });
            throw error;
        } finally {
            runInAction(() => {
                this.isCreating = false;
            });
        }
    }

    public async update(id: number, payload: AdminTrainerUpdateRequest): Promise<AdminTrainer> {
        if (this.isUpdating) {
            throw new Error("A trainer update request is already in progress.");
        }

        runInAction(() => {
            this.isUpdating = true;
            this.mutationError = null;
        });

        try {
            const trainer = await updateAdminTrainer(id, payload);
            this.applyItem(trainer);
            return trainer;
        } catch (error: unknown) {
            runInAction(() => {
                this.mutationError = getAdminErrorMessage(error, "Failed to update trainer.");
            });
            throw error;
        } finally {
            runInAction(() => {
                this.isUpdating = false;
            });
        }
    }

    public async uploadPhoto(id: number, photo: File): Promise<AdminTrainer> {
        runInAction(() => {
            this.isUploadingPhoto = true;
            this.mutationError = null;
        });

        try {
            const trainer = await uploadAdminTrainerPhoto(id, photo);
            this.applyItem(trainer);
            return trainer;
        } catch (error: unknown) {
            runInAction(() => {
                this.mutationError = getAdminErrorMessage(error, "Failed to upload trainer photo.");
            });
            throw error;
        } finally {
            runInAction(() => {
                this.isUploadingPhoto = false;
            });
        }
    }

    public delete(id: number): Promise<AdminTrainer | void> {
        return this.runAction(id, "delete", () => deleteAdminTrainer(id), "Failed to delete trainer.");
    }

    public restore(id: number): Promise<AdminTrainer | void> {
        return this.runAction(id, "restore", () => restoreAdminTrainer(id), "Failed to restore trainer.");
    }

    public block(id: number): Promise<AdminTrainer | void> {
        return this.runAction(id, "block", () => blockAdminTrainer(id), "Failed to block trainer.");
    }

    public unblock(id: number): Promise<AdminTrainer | void> {
        return this.runAction(id, "unblock", () => unblockAdminTrainer(id), "Failed to unblock trainer.");
    }
}

export const adminTrainersStore = new AdminTrainersStore();

