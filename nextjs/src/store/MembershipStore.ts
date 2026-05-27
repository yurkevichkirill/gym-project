import { makeAutoObservable, runInAction } from "mobx";
import MembershipType from "@/types/membership/membership.type";
import {
    buyMembership,
    freezeMembership,
    getAllMemberships,
    renewMembership, 
    terminateMembership,
    unfreezeMembership
} from "@/api/client/memberships.api";
import MembershipBuyType from "@/types/membership/membership-buy.type";
import { MembershipFreezeType } from "@/types/membership/membership-freeze.type";
import { MembershipUnfreezeType } from "@/types/membership/membership-unfreeze.type";
import { MembershipRenewType } from "@/types/membership/membership-renew.type";
import { MembershipTerminateType } from "@/types/membership/membership-terminate.type";
import { authStore } from "@/store/AuthStore";

export interface MembershipStore {
    memberships: MembershipType[];
    isLoading: boolean;

    init: () => Promise<void>;
    buy: (payload: MembershipBuyType) => Promise<MembershipType>;
    freeze: (payload: MembershipFreezeType) => Promise<MembershipType>;
    unfreeze: (payload: MembershipUnfreezeType) => Promise<MembershipType>;
    renew: (payload: MembershipRenewType) => Promise<MembershipType>;
    terminate: (payload: MembershipTerminateType) => Promise<MembershipType>;
}

export const membershipStore: MembershipStore = {
    memberships: [],
    isLoading: false,

    init: async () => {
        runInAction(() => { membershipStore.isLoading = true; });
        try {
            const memberships = await getAllMemberships();
            runInAction(() => { membershipStore.memberships = memberships; });
        } catch (e) {
            console.error(e);
        } finally {
            runInAction(() => { membershipStore.isLoading = false; });
        }
    },

    buy: async (payload: MembershipBuyType) => {
        const res = await buyMembership(payload);
        await Promise.all([
            membershipStore.init(),
            authStore.checkAuth(),
        ]);
        return res;
    },

    freeze: async (payload: MembershipFreezeType) => {
        const updated = await freezeMembership(payload);
        
        runInAction(() => {
            membershipStore.memberships = membershipStore.memberships.map(m => 
                m.id === updated.id ? updated : m
            );
        });
        
        return updated;
    },

    unfreeze: async (payload: MembershipUnfreezeType) => {
        const updated = await unfreezeMembership(payload);
        
        runInAction(() => {
            membershipStore.memberships = membershipStore.memberships.map(m => 
                m.id === updated.id ? updated : m
            );
        });
        
        return updated;
    },

    renew: async (payload: MembershipRenewType) => {
        const res = await renewMembership(payload);
        
        return res;
    },

    terminate: async (payload: MembershipTerminateType) => {
        const updated = await terminateMembership(payload);
        
        runInAction(() => {
            membershipStore.memberships = membershipStore.memberships.map(m => 
                m.id === updated.id ? updated : m
            );
        });
        
        return updated;
    },
};

makeAutoObservable(membershipStore);