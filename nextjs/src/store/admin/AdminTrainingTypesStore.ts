import { runInAction } from "mobx";
import {
    createAdminTrainingType,
    deleteAdminTrainingType,
    getAdminTrainingType,
    getAdminTrainingTypes,
    getAdminTrainingTypesRequestKey,
    updateAdminTrainingType,
    uploadAdminTrainingTypePhoto,
} from "@/api/admin/training-types.api";
import { AdminResourceStore } from "@/store/admin/createAdminResourceStore";
import type { TrainingTypesListParams } from "@/api/public/training-types.api";
import type {
    AdminTrainingType,
    AdminTrainingTypeCreateRequest,
    AdminTrainingTypeUpdateRequest,
} from "@/types/admin/admin-training-type.type";
import { getAdminErrorMessage } from "@/api/admin/admin-api-utils";

class AdminTrainingTypesStore extends AdminResourceStore<AdminTrainingType, TrainingTypesListParams> {
    public isCreating = false;
    public isUpdating = false;
    public isUploadingPhoto = false;

    public constructor() {
        super(getAdminTrainingTypes, getAdminTrainingTypesRequestKey, (id) => getAdminTrainingType(id.toString()));
    }

    public async create(payload: AdminTrainingTypeCreateRequest): Promise<AdminTrainingType> {
        runInAction(() => {
            this.isCreating = true;
            this.mutationError = null;
        });

        try {
            const type = await createAdminTrainingType(payload);
            await this.refetch();
            return type;
        } catch (error: unknown) {
            runInAction(() => {
                this.mutationError = getAdminErrorMessage(error, "Failed to create training type.");
            });
            throw error;
        } finally {
            runInAction(() => {
                this.isCreating = false;
            });
        }
    }

    public async update(id: number, payload: AdminTrainingTypeUpdateRequest): Promise<AdminTrainingType> {
        runInAction(() => {
            this.isUpdating = true;
            this.mutationError = null;
        });

        try {
            const type = await updateAdminTrainingType(id, payload);
            this.applyItem(type);
            return type;
        } catch (error: unknown) {
            runInAction(() => {
                this.mutationError = getAdminErrorMessage(error, "Failed to update training type.");
            });
            throw error;
        } finally {
            runInAction(() => {
                this.isUpdating = false;
            });
        }
    }

    public async uploadPhoto(id: number, photo: File): Promise<AdminTrainingType> {
        runInAction(() => {
            this.isUploadingPhoto = true;
            this.mutationError = null;
        });

        try {
            const type = await uploadAdminTrainingTypePhoto(id, photo);
            this.applyItem(type);
            return type;
        } catch (error: unknown) {
            runInAction(() => {
                this.mutationError = getAdminErrorMessage(error, "Failed to upload training type photo.");
            });
            throw error;
        } finally {
            runInAction(() => {
                this.isUploadingPhoto = false;
            });
        }
    }

    public delete(id: number): Promise<AdminTrainingType | void> {
        return this.runAction(id, "delete", () => deleteAdminTrainingType(id), "Failed to delete training type.");
    }
}

export const adminTrainingTypesStore = new AdminTrainingTypesStore();

