import { action, makeObservable } from "mobx";
import {
    cancelAdminTraining,
    completeAdminTraining,
    getAdminTrainings,
    getAdminTrainingsRequestKey,
    updateAdminTraining,
} from "@/api/admin/trainings.api";
import { AdminResourceStore } from "@/store/admin/createAdminResourceStore";
import type {
    AdminTraining,
    AdminTrainingsGetQueryParams,
    AdminTrainingUpdateRequest,
} from "@/types/admin/admin-training.type";

class AdminTrainingsStore extends AdminResourceStore<AdminTraining, AdminTrainingsGetQueryParams> {
    public constructor() {
        super(getAdminTrainings, getAdminTrainingsRequestKey);
        makeObservable(this, {
            update: action.bound,
            cancel: action.bound,
            complete: action.bound,
        });
    }

    public update(id: number, payload: AdminTrainingUpdateRequest): Promise<AdminTraining | void> {
        return this.runAction(id, "update", () => updateAdminTraining(id, payload), "Failed to update training.");
    }

    public cancel(id: number): Promise<AdminTraining | void> {
        return this.runAction(id, "cancel", () => cancelAdminTraining(id), "Failed to cancel training.");
    }

    public complete(id: number): Promise<AdminTraining | void> {
        return this.runAction(id, "complete", () => completeAdminTraining(id), "Failed to complete training.");
    }
}

export const adminTrainingsStore = new AdminTrainingsStore();

