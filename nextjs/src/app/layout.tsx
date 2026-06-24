import "@/app/globals.css";
import type { Metadata } from "next";
import React, { Suspense } from "react";
import { NavigationProvider } from "@/context/navigation-context";
import Navbar from "@/scenes/navbar";
import Footer from "@/scenes/footer";
import { PaymentReturnSync } from "@/scenes/stripe/PaymentReturnSync";
import { StoreProvider } from "@/store/StoreProvider";
import { Toaster } from "sonner";

export const metadata: Metadata = {
    title: {
        default: "EvoGym",
        template: "%s | EvoGym",
    },
    description: "Gym memberships, personal trainers, and individual training sessions.",
};

const Layout = ({ children }: Readonly<{ children: React.ReactNode }>) => {
    return (
        <html lang="en" className="scroll-smooth">
            <body className="bg-gray-20">
                <div className="app">
                    <StoreProvider>
                        <Suspense fallback={null}>
                            <PaymentReturnSync />
                        </Suspense>
                        <NavigationProvider>
                            <Navbar />
                            {children}
                            <Toaster
                                position="top-right"
                                expand={true}
                                richColors
                                closeButton
                            />
                            <Footer />
                        </NavigationProvider>
                    </StoreProvider>
                </div>
            </body>
        </html>
    );
};

export default Layout;
