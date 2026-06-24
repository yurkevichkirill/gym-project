import {makeAutoObservable, runInAction} from "mobx";
import MembershipType from "@/types/membership/membership.type";
import {
    buyMembership,
    freezeMembership,
    getAllMemberships,
    renewMembership,
    terminateMembership,
    unfreezeMembership,
} from "@/api/client/memberships.api";
import MembershipBuyType from "@/types/membership/membership-buy.type";
import {MembershipFreezeType} from "@/types/membership/membership-freeze.type";
import {MembershipUnfreezeType} from "@/types/membership/membership-unfreeze.type";
import {MembershipRenewType} from "@/types/membership/membership-renew.type";
import {MembershipTerminateType} from "@/types/membership/membership-terminate.type";
import {getErrorMessage} from "@/lib/getErrorMessage";
import {clientStore} from "@/store/ClientStore";
import {authStore} from "@/store/AuthStore";

type InitTask = {
    generation: number;
    promise: Promise<void>;
};

class MembershipStore {
    public memberships: MembershipType[] = [];
    public isLoading = false;
    public error: string | null = null;

    private generation = 0;
    private initTask: InitTask | null = null;

    public constructor() {
        makeAutoObservable(this, {
            generation: false,
            initTask: false,
        }, {autoBind: true});
    }

    public init(): Promise<void> {
        if (!authStore.isAuth) {
            this.reset();
            return Promise.resolve();
        }

        const generation = this.generation;
        if (this.initTask?.generation === generation) {
            return this.initTask.promise;
        }

        const promise = this.load(generation).finally(() => {
            if (this.initTask?.promise === promise) {
                this.initTask = null;
            }
        });

        this.initTask = {generation, promise};

        return promise;
    }

    public async buy(payload: MembershipBuyType): Promise<MembershipType> {
        const generation = this.generation;
        const membership = await buyMembership(payload);

        if (generation === this.generation && authStore.isAuth) {
            await Promise.all([
                this.init(),
                clientStore.init(),
            ]);
        }

        return membership;
    }

    public async freeze(payload: MembershipFreezeType): Promise<MembershipType> {
        const generation = this.generation;
        const updated = await freezeMembership(payload);

        if (generation === this.generation && authStore.isAuth) {
            runInAction(() => {
                this.memberships = this.memberships.map((membership) => (
                    membership.id === updated.id ? updated : membership
                ));
            });
        }

        return updated;
    }

    public async unfreeze(payload: MembershipUnfreezeType): Promise<MembershipType> {
        const generation = this.generation;
        const updated = await unfreezeMembership(payload);

        if (generation === this.generation && authStore.isAuth) {
            runInAction(() => {
                this.memberships = this.memberships.map((membership) => (
                    membership.id === updated.id ? updated : membership
                ));
            });
        }

        return updated;
    }

    public async renew(payload: MembershipRenewType): Promise<MembershipType> {
        return renewMembership(payload);
    }

    public async terminate(payload: MembershipTerminateType): Promise<MembershipType> {
        const generation = this.generation;
        const updated = await terminateMembership(payload);

        if (generation === this.generation && authStore.isAuth) {
            runInAction(() => {
                this.memberships = this.memberships.map((membership) => (
                    membership.id === updated.id ? updated : membership
                ));
            });
        }

        return updated;
    }

    public reset(): void {
        this.generation += 1;
        this.initTask = null;
        this.memberships = [];
        this.isLoading = false;
        this.error = null;
    }

    private async load(generation: number): Promise<void> {
        runInAction(() => {
            this.isLoading = true;
            this.error = null;
        });

        try {
            const memberships = await getAllMemberships();

            if (generation === this.generation && authStore.isAuth) {
                runInAction(() => {
                    this.memberships = memberships;
                });
            }
        } catch (error: unknown) {
            if (generation === this.generation && authStore.isAuth) {
                runInAction(() => {
                    this.error = getErrorMessage(error, "Failed to load memberships.");
                });
            }
        } finally {
            if (generation === this.generation) {
                runInAction(() => {
                    this.isLoading = false;
                });
            }
        }
    }
}

export const membershipStore = new MembershipStore();
