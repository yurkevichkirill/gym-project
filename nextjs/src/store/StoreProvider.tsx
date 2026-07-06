'use client'

import {authStore} from "@/store/AuthStore";
import {createContext, useContext, useEffect} from "react";
import {clientStore} from "@/store/ClientStore";
import {bookingStore} from "@/store/BookingStore";
import {membershipStore} from "@/store/MembershipStore";
import {paymentStore} from "@/store/PaymentStore";
import {trainerStore} from "@/store/TrainerStore";
import {trainerWorktimeStore} from "@/store/TrainerWorktimeStore";
import {trainerTrainingStore} from "@/store/TrainerTrainingStore";
import {adminClientsStore} from "@/store/admin/AdminClientsStore";
import {adminTrainersStore} from "@/store/admin/AdminTrainersStore";
import {adminBookingsStore} from "@/store/admin/AdminBookingsStore";
import {adminMembershipsStore} from "@/store/admin/AdminMembershipsStore";
import {adminMembershipPlansStore} from "@/store/admin/AdminMembershipPlansStore";
import {adminPaymentsStore} from "@/store/admin/AdminPaymentsStore";
import {adminTrainingTypesStore} from "@/store/admin/AdminTrainingTypesStore";
import {adminTrainingsStore} from "@/store/admin/AdminTrainingsStore";
import {adminWorktimesStore} from "@/store/admin/AdminWorktimesStore";

interface StoreContextType {
    authStore: typeof authStore;
    clientStore: typeof clientStore;
    trainerStore: typeof trainerStore;
    trainerWorktimeStore: typeof trainerWorktimeStore;
    trainerTrainingStore: typeof trainerTrainingStore;
    bookingStore: typeof bookingStore;
    membershipStore: typeof membershipStore;
    paymentStore: typeof paymentStore;
    adminClientsStore: typeof adminClientsStore;
    adminTrainersStore: typeof adminTrainersStore;
    adminBookingsStore: typeof adminBookingsStore;
    adminMembershipsStore: typeof adminMembershipsStore;
    adminMembershipPlansStore: typeof adminMembershipPlansStore;
    adminPaymentsStore: typeof adminPaymentsStore;
    adminTrainingTypesStore: typeof adminTrainingTypesStore;
    adminTrainingsStore: typeof adminTrainingsStore;
    adminWorktimesStore: typeof adminWorktimesStore;
}

const resetUserStores = (): void => {
    clientStore.reset();
    trainerStore.reset();
    trainerWorktimeStore.reset();
    trainerTrainingStore.reset();
    bookingStore.reset();
    membershipStore.reset();
    paymentStore.reset();
    adminClientsStore.reset();
    adminTrainersStore.reset();
    adminBookingsStore.reset();
    adminMembershipsStore.reset();
    adminMembershipPlansStore.reset();
    adminPaymentsStore.reset();
    adminTrainingTypesStore.reset();
    adminTrainingsStore.reset();
    adminWorktimesStore.reset();
};

authStore.configureUserStoresReset(resetUserStores);

const StoreContext = createContext<StoreContextType | null>(null);

export const StoreProvider = ({
    children,
}: {
    children: React.ReactNode;
}) => {
    useEffect(() => {
        void authStore.checkAuth();
    }, []);

    return (
        <StoreContext.Provider value={{
            authStore,
            clientStore,
            trainerStore,
            trainerWorktimeStore,
            trainerTrainingStore,
            bookingStore,
            membershipStore,
            paymentStore,
            adminClientsStore,
            adminTrainersStore,
            adminBookingsStore,
            adminMembershipsStore,
            adminMembershipPlansStore,
            adminPaymentsStore,
            adminTrainingTypesStore,
            adminTrainingsStore,
            adminWorktimesStore,
        }}>
            {children}
        </StoreContext.Provider>
    );
};

export const useStore = (): StoreContextType => {
    const ctx = useContext(StoreContext);

    if (!ctx) {
        throw new Error("StoreProvider missing");
    }

    return ctx;
};
