import { runInAction } from "mobx";
import {
    cancelAdminMembership,
    createAdminClientMembership,
    freezeAdminMembership,
    getAdminMembership,
    getAdminMemberships,
    getAdminMembershipsRequestKey,
    renewAdminMembership,
    terminateAdminMembership,
    unfreezeAdminMembership,
} from "@/api/admin/memberships.api";
import { AdminResourceStore } from "@/store/admin/createAdminResourceStore";
import type {
    AdminMembership,
    AdminMembershipCreateRequest,
    AdminMembershipsGetQueryParams,
} from "@/types/admin/admin-membership.type";
import { getAdminErrorMessage } from "@/api/admin/admin-api-utils";

class AdminMembershipsStore extends AdminResourceStore<AdminMembership, AdminMembershipsGetQueryParams> {
    public isCreating = false;

    public constructor() {
        super(getAdminMemberships, getAdminMembershipsRequestKey, getAdminMembership);
    }

    public async createForClient(clientId: number, payload: AdminMembershipCreateRequest): Promise<AdminMembership> {
        runInAction(() => {
            this.isCreating = true;
            this.mutationError = null;
        });

        try {
            const membership = await createAdminClientMembership(clientId, payload);
            await this.refetch();
            return membership;
        } catch (error: unknown) {
            runInAction(() => {
                this.mutationError = getAdminErrorMessage(error, "Failed to create membership.");
            });
            throw error;
        } finally {
            runInAction(() => {
                this.isCreating = false;
            });
        }
    }

    public cancel(id: number): Promise<AdminMembership | void> {
        return this.runAction(id, "cancel", () => cancelAdminMembership(id), "Failed to cancel membership.");
    }

    public freeze(id: number): Promise<AdminMembership | void> {
        return this.runAction(id, "freeze", () => freezeAdminMembership(id), "Failed to freeze membership.");
    }

    public unfreeze(id: number): Promise<AdminMembership | void> {
        return this.runAction(id, "unfreeze", () => unfreezeAdminMembership(id), "Failed to unfreeze membership.");
    }

    public renew(id: number): Promise<AdminMembership | void> {
        return this.runAction(id, "renew", () => renewAdminMembership(id), "Failed to renew membership.");
    }

    public terminate(id: number): Promise<AdminMembership | void> {
        return this.runAction(id, "terminate", () => terminateAdminMembership(id), "Failed to terminate membership.");
    }
}

export const adminMembershipsStore = new AdminMembershipsStore();

