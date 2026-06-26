'use client'

import {authStore} from "@/store/AuthStore";
import {createContext, useContext, useEffect} from "react";
import {clientStore} from "@/store/ClientStore";
import {bookingStore} from "@/store/BookingStore";
import {membershipStore} from "@/store/MembershipStore";
import {paymentStore} from "@/store/PaymentStore";

interface StoreContextType {
    authStore: typeof authStore;
    clientStore: typeof clientStore;
    bookingStore: typeof bookingStore;
    membershipStore: typeof membershipStore;
    paymentStore: typeof paymentStore;
}

const resetUserStores = (): void => {
    clientStore.reset();
    bookingStore.reset();
    membershipStore.reset();
    paymentStore.reset();
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
        <StoreContext.Provider value={{authStore, clientStore, bookingStore, membershipStore, paymentStore}}>
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
