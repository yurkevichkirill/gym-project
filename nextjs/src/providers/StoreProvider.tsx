'use client'

import {authStore} from "@/stores/AuthStore";
import {createContext, useContext} from "react";

interface StoreContextType {
    authStore: typeof authStore;
}

const StoreContext = createContext<StoreContextType | null>(null);

export const StoreProvider = ({
    children,
}: {
    children: React.ReactNode;
}) => {
    return (
        <StoreContext.Provider value={{ authStore }}>
            {children}
        </StoreContext.Provider>
    );
};

export const useStore = (): StoreContextType => {
    const ctx = useContext(StoreContext);

    if (!ctx) {
        throw new Error('StoreProvider missing');
    }

    return ctx;
}