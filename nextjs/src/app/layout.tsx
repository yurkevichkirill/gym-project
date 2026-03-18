import {NavigationProvider} from "@/context/navigation-context";
import Navbar from "@/scenes/navbar";
import Footer from "@/scenes/footer";
import React from "react";

const Layout = ({ children }: {children: React.ReactNode}) => {
    return (
        <html className="scroll-smooth">
            <body>
                <div className="app bg-gray-20">
                    <NavigationProvider>
                        <Navbar />
                        {children}
                        <Footer />
                    </NavigationProvider>
                </div>
            </body>
        </html>
    );
}

export default Layout;