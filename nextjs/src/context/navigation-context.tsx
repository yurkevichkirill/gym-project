'use client'

import {createContext, useContext, useEffect, useState} from "react";
import {SelectedPage} from "@/shared/types";

type NavigationType = {
    selectedPage: SelectedPage;
    setSelectedPage: (page: SelectedPage) => void;
    isTopOfPage: boolean;
};

const NavigationContext = createContext<NavigationType | undefined>(undefined);

export const NavigationProvider = ({ children }: { children: React.ReactNode }) => {
    const [selectedPage, setSelectedPage] = useState<SelectedPage>(SelectedPage.Home);
    const [isTopOfPage, setIsTopOfPage] = useState<boolean>(true);

    useEffect(() => {
        const handleScroll = () => {
            if (window.scrollY === 0) {
                setIsTopOfPage(true)
                setSelectedPage(SelectedPage.Home);
            }
            if (window.scrollY !== 0) setIsTopOfPage(false);
        };
        window.addEventListener("scroll", handleScroll);

        return () => window.removeEventListener("scroll", handleScroll);
    }, []);

    return (
        <NavigationContext.Provider
            value={{
                selectedPage,
                setSelectedPage,
                isTopOfPage
            }}
        >
            {children}
        </NavigationContext.Provider>
    );
}

export const useNavigation = () => {
    const context = useContext(NavigationContext);

    if (!context) {
        throw new Error("useNavigation must be used inside NavigationProvider");
    }

    return context;
}

