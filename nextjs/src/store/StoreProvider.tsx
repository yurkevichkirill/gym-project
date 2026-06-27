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
