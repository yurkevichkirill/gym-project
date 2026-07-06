import { makeAutoObservable, runInAction } from "mobx";
import MembershipType from "@/types/membership/membership.type";
import {
    buyMembership,
    cancelMembership,
    freezeMembership,
    getAllMemberships,
    getMembership,
    getMembershipsRequestKey,
    renewMembership,
    terminateMembership,
    unfreezeMembership,
} from "@/api/client/memberships.api";
import MembershipBuyType from "@/types/membership/membership-buy.type";
import { MembershipFreezeType } from "@/types/membership/membership-freeze.type";
import { MembershipUnfreezeType } from "@/types/membership/membership-unfreeze.type";
import { MembershipRenewType } from "@/types/membership/membership-renew.type";
import { MembershipTerminateType } from "@/types/membership/membership-terminate.type";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { clientStore } from "@/store/ClientStore";
import { paymentStore } from "@/store/PaymentStore";
import { authStore } from "@/store/AuthStore";
import { ApiClientError } from "@/lib/apiClient";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { MembershipsGetQueryParams } from "@/types/membership/memberships-get.type";

type MembershipPagination = ApiCollectionResponse<MembershipType[]>["meta"]["pagination"];

type InitTask = {
    generation: number;
    requestId: number;
    requestKey: string;
    promise: Promise<void>;
};

type DetailTask = {
    generation: number;
    requestId: number;
    membershipId: number;
    promise: Promise<void>;
};

type MembershipStorePrivateKey =
    | "generation"
    | "listRequestId"
    | "detailRequestId"
    | "currentParams"
    | "currentRequestKey"
    | "initTask"
    | "detailTask"
    | "purchaseTask"
    | "mutationTasks";

const getErrorStatus = (error: unknown): number | null => {
    return error instanceof ApiClientError ? error.status : null;
};

const shouldResynchronizeAfterError = (error: unknown): boolean => {
    return error instanceof ApiClientError
        && (error.status === 409 || error.status === 422);
};

class MembershipStore {
    public memberships: MembershipType[] = [];
    public pagination: MembershipPagination | null = null;
    public sort: Record<string, string> = {};
    public loadedRequestKey: string | null = null;
    public isLoading = false;
    public isRefreshing = false;
    public error: string | null = null;
    public errorStatus: number | null = null;

    public selectedMembership: MembershipType | null = null;
    public isDetailLoading = false;
    public detailError: string | null = null;
    public detailErrorStatus: number | null = null;

    public purchasingPlanId: number | null = null;
    public mutatingMembershipIds: number[] = [];

    private generation = 0;
    private listRequestId = 0;
    private detailRequestId = 0;
    private currentParams: MembershipsGetQueryParams = {};
    private currentRequestKey = "";
    private initTask: InitTask | null = null;
    private detailTask: DetailTask | null = null;
    private purchaseTask: Promise<MembershipType> | null = null;
    private mutationTasks = new Map<number, Promise<MembershipType>>();

    public constructor() {
        makeAutoObservable<this, MembershipStorePrivateKey>(this, {
            generation: false,
            listRequestId: false,
            detailRequestId: false,
            currentParams: false,
            currentRequestKey: false,
            initTask: false,
            detailTask: false,
            purchaseTask: false,
            mutationTasks: false,
        }, { autoBind: true });
    }

    public get isPurchasing(): boolean {
        return this.purchasingPlanId !== null;
    }

    public init(params: MembershipsGetQueryParams = {}): Promise<void> {
        if (!authStore.isAuth) {
            this.reset();
            return Promise.resolve();
        }

        const generation = this.generation;
        const requestKey = getMembershipsRequestKey(params);

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

    public async loadMembership(membershipId: number): Promise<void> {
        if (!authStore.isAuth) {
            this.detailRequestId += 1;
            this.detailTask = null;
            this.resetDetail();
            return;
        }

        const generation = this.generation;

        if (
            this.detailTask?.generation === generation
            && this.detailTask.membershipId === membershipId
        ) {
            return this.detailTask.promise;
        }

        const requestId = ++this.detailRequestId;
        const promise = this.loadDetail(generation, requestId, membershipId).finally(() => {
            if (this.detailTask?.requestId === requestId) {
                this.detailTask = null;
            }
        });

        this.detailTask = { generation, requestId, membershipId, promise };

        return promise;
    }

    public isMutating(membershipId: number): boolean {
        return this.mutatingMembershipIds.includes(membershipId);
    }

    public buy(payload: MembershipBuyType): Promise<MembershipType> {
        if (this.purchaseTask !== null) {
            return this.purchaseTask;
        }

        runInAction(() => {
            this.purchasingPlanId = payload.membershipPlanId;
        });

        const task = this.buyInternal(payload).finally(() => {
            if (this.purchaseTask === task) {
                this.purchaseTask = null;

                runInAction(() => {
                    this.purchasingPlanId = null;
                });
            }
        });

        this.purchaseTask = task;

        return task;
    }

    public cancel({ id }: { id: number }): Promise<MembershipType> {
        return this.mutate(id, () => cancelMembership(id));
    }

    public freeze(payload: MembershipFreezeType): Promise<MembershipType> {
        return this.mutate(payload.id, () => freezeMembership(payload));
    }

    public unfreeze(payload: MembershipUnfreezeType): Promise<MembershipType> {
        return this.mutate(payload.id, () => unfreezeMembership(payload));
    }

    public renew(payload: MembershipRenewType): Promise<MembershipType> {
        return this.mutate(payload.id, () => renewMembership(payload));
    }

    public terminate(payload: MembershipTerminateType): Promise<MembershipType> {
        return this.mutate(payload.id, () => terminateMembership(payload));
    }

    public async refreshAfterPayment(membershipId?: number): Promise<void> {
        if (!authStore.isAuth) {
            return;
        }

        await this.syncAfterMutation(membershipId);
    }

    public reset(): void {
        this.generation += 1;
        this.listRequestId += 1;
        this.detailRequestId += 1;
        this.initTask = null;
        this.detailTask = null;
        this.purchaseTask = null;
        this.mutationTasks.clear();
        this.currentParams = {};
        this.currentRequestKey = "";
        this.memberships = [];
        this.pagination = null;
        this.sort = {};
        this.loadedRequestKey = null;
        this.isLoading = false;
        this.isRefreshing = false;
        this.error = null;
        this.errorStatus = null;
        this.purchasingPlanId = null;
        this.mutatingMembershipIds = [];
        this.resetDetail();
    }

    private mutate(
        membershipId: number,
        request: () => Promise<MembershipType>,
    ): Promise<MembershipType> {
        const existingTask = this.mutationTasks.get(membershipId);

        if (existingTask !== undefined) {
            return existingTask;
        }

        runInAction(() => {
            this.mutatingMembershipIds = [...this.mutatingMembershipIds, membershipId];
        });

        const task = this.mutateInternal(membershipId, request).finally(() => {
            if (this.mutationTasks.get(membershipId) === task) {
                this.mutationTasks.delete(membershipId);

                runInAction(() => {
                    this.mutatingMembershipIds = this.mutatingMembershipIds.filter(
                        (id) => id !== membershipId,
                    );
                });
            }
        });

        this.mutationTasks.set(membershipId, task);

        return task;
    }

    private async buyInternal(payload: MembershipBuyType): Promise<MembershipType> {
        const generation = this.generation;

        try {
            const membership = await buyMembership(payload);

            if (generation === this.generation && authStore.isAuth) {
                this.applyMutationResponse(membership);
                await this.syncAfterMutation();
            }

            return membership;
        } catch (error: unknown) {
            if (
                generation === this.generation
                && authStore.isAuth
                && shouldResynchronizeAfterError(error)
            ) {
                await this.syncAfterMutation();
            }

            throw error;
        }
    }

    private async mutateInternal(
        membershipId: number,
        request: () => Promise<MembershipType>,
    ): Promise<MembershipType> {
        const generation = this.generation;

        try {
            const membership = await request();

            if (generation === this.generation && authStore.isAuth) {
                this.applyMutationResponse(membership);
                await this.syncAfterMutation(membershipId);
            }

            return membership;
        } catch (error: unknown) {
            if (
                generation === this.generation
                && authStore.isAuth
                && shouldResynchronizeAfterError(error)
            ) {
                await this.syncAfterMutation(membershipId);
            }

            throw error;
        }
    }

    private applyMutationResponse(membership: MembershipType): void {
        this.listRequestId += 1;

        if (this.selectedMembership?.id === membership.id) {
            this.detailRequestId += 1;
        }

        runInAction(() => {
            const index = this.memberships.findIndex((item) => item.id === membership.id);

            if (index === -1) {
                this.memberships = [membership, ...this.memberships];
            } else {
                this.memberships = this.memberships.map((item) => (
                    item.id === membership.id ? membership : item
                ));
            }

            if (this.selectedMembership?.id === membership.id) {
                this.selectedMembership = membership;
            }
        });
    }

    private async syncAfterMutation(membershipId?: number): Promise<void> {
        const detailId = membershipId !== undefined
            && this.selectedMembership?.id === membershipId
            ? membershipId
            : null;
        const tasks: Promise<void>[] = [
            this.refreshList(),
            clientStore.init(),
            paymentStore.init(),
        ];

        if (detailId !== null) {
            tasks.push(this.refreshDetail(detailId));
        }

        await Promise.all(tasks);
    }

    private refreshList(): Promise<void> {
        this.initTask = null;

        return this.init(this.currentParams);
    }

    private refreshDetail(membershipId: number): Promise<void> {
        this.detailTask = null;

        return this.loadMembership(membershipId);
    }

    private resetDetail(): void {
        this.selectedMembership = null;
        this.isDetailLoading = false;
        this.detailError = null;
        this.detailErrorStatus = null;
    }

    private async load(
        generation: number,
        requestId: number,
        params: MembershipsGetQueryParams,
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
            const response = await getAllMemberships(params);

            if (
                generation === this.generation
                && requestId === this.listRequestId
                && requestKey === this.currentRequestKey
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.memberships = response.data;
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
                    this.error = getErrorMessage(error, "Failed to load memberships.");
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
        membershipId: number,
    ): Promise<void> {
        runInAction(() => {
            if (this.selectedMembership?.id !== membershipId) {
                this.selectedMembership = null;
            }

            this.isDetailLoading = true;
            this.detailError = null;
            this.detailErrorStatus = null;
        });

        try {
            const membership = await getMembership(membershipId);

            if (
                generation === this.generation
                && requestId === this.detailRequestId
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.selectedMembership = membership;
                });
            }
        } catch (error: unknown) {
            if (
                generation === this.generation
                && requestId === this.detailRequestId
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.detailError = getErrorMessage(error, "Failed to load membership details.");
                    this.detailErrorStatus = getErrorStatus(error);
                });
            }
        } finally {
            if (generation === this.generation && requestId === this.detailRequestId) {
                runInAction(() => {
                    this.isDetailLoading = false;
                });
            }
        }
    }
}

export const membershipStore = new MembershipStore();
