import { runInAction } from "mobx";
import {
    createAdminTrainerWorktime,
    deleteAdminWorktime,
    getAdminWorktimes,
    getAdminWorktimesRequestKey,
    updateAdminWorktime,
} from "@/api/admin/worktimes.api";
import { AdminResourceStore } from "@/store/admin/createAdminResourceStore";
import type { GetWorktimesType } from "@/types/worktime/worktimes-get.type";
import WorktimeData from "@/types/trainer/public/worktime.type";
import {
    AdminWorktimeCreateRequest,
    AdminWorktimeUpdateRequest,
} from "@/types/admin/admin-worktime.type";
import { getAdminErrorMessage } from "@/api/admin/admin-api-utils";

class AdminWorktimesStore extends AdminResourceStore<WorktimeData, GetWorktimesType> {
    public isCreating = false;
    public isUpdating = false;

    public constructor() {
        super(getAdminWorktimes, getAdminWorktimesRequestKey);
    }

    public async createForTrainer(trainerId: number, payload: AdminWorktimeCreateRequest): Promise<WorktimeData> {
        runInAction(() => {
            this.isCreating = true;
            this.mutationError = null;
        });

        try {
            const worktime = await createAdminTrainerWorktime(trainerId, payload);
            await this.refetch();
            return worktime;
        } catch (error: unknown) {
            runInAction(() => {
                this.mutationError = getAdminErrorMessage(error, "Failed to create worktime.");
            });
            throw error;
        } finally {
            runInAction(() => {
                this.isCreating = false;
            });
        }
    }

    public async update(id: number, payload: AdminWorktimeUpdateRequest): Promise<WorktimeData> {
        runInAction(() => {
            this.isUpdating = true;
            this.mutationError = null;
        });

        try {
            const worktime = await updateAdminWorktime(id, payload);
            this.applyItem(worktime);
            return worktime;
        } catch (error: unknown) {
            runInAction(() => {
                this.mutationError = getAdminErrorMessage(error, "Failed to update worktime.");
            });
            throw error;
        } finally {
            runInAction(() => {
                this.isUpdating = false;
            });
        }
    }

    public delete(id: number): Promise<WorktimeData | void> {
        return this.runAction(id, "delete", () => deleteAdminWorktime(id), "Failed to delete worktime.");
    }
}

export const adminWorktimesStore = new AdminWorktimesStore();

