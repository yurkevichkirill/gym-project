import { runInAction } from "mobx";
import {
    createAdminMembershipPlan,
    deleteAdminMembershipPlan,
    getAdminMembershipPlan,
    getAdminMembershipPlans,
    getAdminMembershipPlansRequestKey,
    updateAdminMembershipPlan,
} from "@/api/admin/membership-plans.api";
import { AdminResourceStore } from "@/store/admin/createAdminResourceStore";
import type { MembershipPlansListParams } from "@/api/public/membership-plans.api";
import type {
    AdminMembershipPlan,
    AdminMembershipPlanCreateRequest,
    AdminMembershipPlanUpdateRequest,
} from "@/types/admin/admin-membership-plan.type";
import { getAdminErrorMessage } from "@/api/admin/admin-api-utils";

class AdminMembershipPlansStore extends AdminResourceStore<AdminMembershipPlan, MembershipPlansListParams> {
    public isCreating = false;
    public isUpdating = false;

    public constructor() {
        super(getAdminMembershipPlans, getAdminMembershipPlansRequestKey, (id) => getAdminMembershipPlan(id.toString()));
    }

    public async create(payload: AdminMembershipPlanCreateRequest): Promise<AdminMembershipPlan> {
        runInAction(() => {
            this.isCreating = true;
            this.mutationError = null;
        });

        try {
            const plan = await createAdminMembershipPlan(payload);
            await this.refetch();
            return plan;
        } catch (error: unknown) {
            runInAction(() => {
                this.mutationError = getAdminErrorMessage(error, "Failed to create membership plan.");
            });
            throw error;
        } finally {
            runInAction(() => {
                this.isCreating = false;
            });
        }
    }

    public async update(id: number, payload: AdminMembershipPlanUpdateRequest): Promise<AdminMembershipPlan> {
        runInAction(() => {
            this.isUpdating = true;
            this.mutationError = null;
        });

        try {
            const plan = await updateAdminMembershipPlan(id, payload);
            this.applyItem(plan);
            return plan;
        } catch (error: unknown) {
            runInAction(() => {
                this.mutationError = getAdminErrorMessage(error, "Failed to update membership plan.");
            });
            throw error;
        } finally {
            runInAction(() => {
                this.isUpdating = false;
            });
        }
    }

    public delete(id: number): Promise<AdminMembershipPlan | void> {
        return this.runAction(id, "delete", () => deleteAdminMembershipPlan(id), "Failed to delete membership plan.");
    }
}

export const adminMembershipPlansStore = new AdminMembershipPlansStore();

