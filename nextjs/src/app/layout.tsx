import {NavigationProvider} from "@/context/navigation-context";
import Navbar from "@/scenes/navbar";
import Footer from "@/scenes/footer";
import React from "react";
import {StoreProvider} from "@/providers/StoreProvider";

const Layout = ({ children }: {children: React.ReactNode}) => {
    return (
        <html className="scroll-smooth">
            <body>
                <div className="app bg-gray-20">
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