'use client'

import {useNavigation} from "@/context/navigation-context";
import {useEffect, useState} from "react";
import Image from "next/image"
import useMediaQuery from "@/hooks/useMediaQuery";
import Logo from "@/assets/Logo.png"
import NavLink from "@/scenes/navbar/NavLink";
import {Bars3Icon, XMarkIcon} from "@heroicons/react/24/solid";
import LoginModal from "@/scenes/authorization";
import {useStore} from "@/store/StoreProvider";
import Link from "next/link";
import {observer} from "mobx-react-lite";

const Navbar =  observer (() => {
    const { selectedPage, setSelectedPage, isTopOfPage } = useNavigation();

    const flexBetween = "flex items-center justify-between";
    const [isMenuToggled, setIsMenuToggled] = useState<boolean>(false);
    const isAboveMediumScreens = useMediaQuery("(min-width: 1250px)");
    const navbarBackground = isTopOfPage ? "bg-gray-20" : "bg-primary-100 drop-shadow";
    const [isOpen, setIsOpen] = useState(false);
    const { authStore } = useStore();

    useEffect(() => {
        if (!isOpen) return;

        const scrollBarWidth = window.innerWidth - document.documentElement.clientWidth;

        document.body.style.overflow = "hidden";
        document.body.style.paddingRight = `${scrollBarWidth}px`;

        return () => {
            document.body.style.overflow = "";
            document.body.style.paddingRight = "";
        };
    }, [isOpen]);

    return (
    <nav>
        <div
            className={`${navbarBackground} ${flexBetween} fixed top-0 z-30 w-full py-6`}
        >
            <div className={`${flexBetween} mx-auto w-5/6`}>
                <div className={`${flexBetween} w-full gap-16`}>
                    {/*LEFT SIDE*/}
                    <Image src={Logo} alt="logo"/>

                    {/*RIGHT SIDE*/}
                    {isAboveMediumScreens ? (
                        <div className={`${flexBetween} w-full`}>
                            <div className={`${flexBetween} gap-8 text-sm`}>
                                <NavLink
                                    page="Home"
                                    selectedPage={selectedPage}
                                    setSelectedPage = {setSelectedPage}
                                />
                                <NavLink
                                    page="Our Trainers"
                                    selectedPage={selectedPage}
                                    setSelectedPage={setSelectedPage}
                                />
                                <NavLink
                                    page="Memberships"
                                    selectedPage={selectedPage}
                                    setSelectedPage = {setSelectedPage}
                                />
                                <NavLink
                                    page="Training Types"
                                    selectedPage={selectedPage}
                                    setSelectedPage={setSelectedPage}
                                />
                                {/*<NavLink*/}
                                {/*    page="Contact Us"*/}
                                {/*    selectedPage={selectedPage}*/}
                                {/*    setSelectedPage = {setSelectedPage}*/}
                                {/*/>*/}
                            </div>
                            {authStore.isAuth ?
                                <div className={`${flexBetween} gap-8`}>
                                    <p>
                                        {authStore.user?.email}
                                    </p>
                                    <Link
                                        className="rounded-md cursor-pointer bg-secondary-500 px-10 py-2 hover:bg-primary-500 hover:text-white"
                                        href={"/me"}
                                    >
                                        My Profile
                                    </Link>
                                    <button className="hover:text-primary-500 cursor-pointer" type="button" onClick={() => authStore.logout()}>
                                        Logout
                                    </button>
                                </div> :
                                <div className={`${flexBetween} gap-8`}>
                                    <button className="hover:text-secondary-500 cursor-pointer" type="button" onClick={() => setIsOpen(true)}>
                                        Sign In
                                    </button>
                                    <LoginModal
                                        isOpen={isOpen}
                                        onClose={() => setIsOpen(false)}
                                    />
                                    <button className="rounded-md cursor-pointer bg-secondary-500 px-10 py-2 hover:bg-primary-500 hover:text-white">Become a member</button>
                                </div>
                            }
                        </div>)
                        : (
                            <div>
                                <button
                                    className="rounded-full bg-secondary-500 p-2"
                                    onClick={() => setIsMenuToggled(!isMenuToggled)}
                                >
                                    <Bars3Icon className="h-6 w-6 text-white" />
                                </button>
                            </div>
                        )}
                </div>
            </div>
        </div>

        {/* MOBILE MENU MODAL */}
        {!isAboveMediumScreens && isMenuToggled && (
            <div className="fixed right-0 bottom-0 z-40 h-full w-[300px] bg-primary-100 drop-shadow-xl">
                {/* CLOSE ICON */}
                <div className="flex justify-end p-12">
                    <button onClick={() => setIsMenuToggled(!isMenuToggled)}>
                        <XMarkIcon className="h-6 w-6 text-gray-400" />
                    </button>
                </div>

                {/* MENU ITEMS */}
                <div className="ml-[22%] flex flex-col gap-10 text-2xl">
                    {
                        authStore.isAuth ?
                        <>
                            <div className="flex flex-col gap-2">
                                <p className="text-[16px]">{authStore.user?.email}</p>
                                <div className="bg-gray-500 h-[2px] w-3/4"></div>
                            </div>
                            <Link
                                className="cursor-pointer text-left transition duration-500 hover:text-primary-300"
                                href={"/me"}
                            >
                                My Profile
                            </Link>
                            <button
                                className="cursor-pointer text-left transition duration-500 hover:text-primary-300"
                                type="button" onClick={() => authStore.logout()}>
                                Logout
                            </button>
                        </>
                        :
                            <button
                                className="cursor-pointer text-left transition duration-500 hover:text-primary-300"
                                type="button" onClick={() => setIsOpen(true)}>
                                Sign In
                            </button>
                    }
                    <LoginModal
                        isOpen={isOpen}
                        onClose={() => setIsOpen(false)}
                    />
                    <NavLink
                        page="Home"
                        selectedPage={selectedPage}
                        setSelectedPage = {setSelectedPage}
                    />
                    <NavLink
                        page="Our Trainers"
                        selectedPage={selectedPage}
                        setSelectedPage={setSelectedPage}
                    />
                    <NavLink
                        page="Memberships"
                        selectedPage={selectedPage}
                        setSelectedPage = {setSelectedPage}
                    />
                    <NavLink
                        page="Training Types"
                        selectedPage={selectedPage}
                        setSelectedPage = {setSelectedPage}
                    />{/*<NavLink*/}
                    {/*    page="Contact Us"*/}
                    {/*    selectedPage={selectedPage}*/}
                    {/*    setSelectedPage = {setSelectedPage}*/}
                    {/*/>*/}
                </div>
            </div>
        )}
    </nav>
    );
});

export default Navbar