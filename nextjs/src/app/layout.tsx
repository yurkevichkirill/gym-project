import {NavigationProvider} from "@/context/navigation-context";
import Navbar from "@/scenes/navbar";
import Footer from "@/scenes/footer";
import React from "react";
import {StoreProvider} from "@/providers/StoreProvider";

const Layout = ({ children }: {children: React.ReactNode}) => {
    return (
        <html className="scroll-smooth">
            <body className="bg-gray-20">
                <div className="app">
                    <StoreProvider>
                        <NavigationProvider>
                            <Navbar />
                            {children}
                            <Footer />
                        </NavigationProvider>
                    </StoreProvider>
                </div>
            </body>
        </html>
    );
}

export default Layout;