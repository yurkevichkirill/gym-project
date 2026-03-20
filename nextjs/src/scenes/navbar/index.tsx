'use client'

import {useNavigation} from "@/context/navigation-context";
import {useState} from "react";
import Image from "next/image"
import useMediaQuery from "@/hooks/useMediaQuery";
import Logo from "@/assets/Logo.png"
import NavLink from "@/scenes/navbar/NavLink";
import ActionButton from "@/shared/ActionButton";
import {Bars3Icon, XMarkIcon} from "@heroicons/react/24/solid"

const Navbar =  () => {
    const { selectedPage, setSelectedPage, isTopOfPage } = useNavigation();

    const flexBetween = "flex items-center justify-between";
    const [isMenuToggled, setIsMenuToggled] = useState<boolean>(false);
    const isAboveMediumScreens = useMediaQuery("(min-width: 1060px)");
    const navbarBackground = isTopOfPage ? "" : "bg-primary-100 drop-shadow";

    return (
    <nav>
        <div
            className={`${navbarBackground} ${flexBetween} fixed top-0 z-999 w-full py-6`}
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
                                <NavLink
                                    page="Contact Us"
                                    selectedPage={selectedPage}
                                    setSelectedPage = {setSelectedPage}
                                />
                            </div>
                            <div className={`${flexBetween} gap-8`}>
                                <p>Sign In</p>
                                <ActionButton setSelectedPage={setSelectedPage}>Become a member</ActionButton>
                            </div>
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
                <div className="ml-[33%] flex flex-col gap-10 text-2xl">
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
                        page="Benefits"
                        selectedPage={selectedPage}
                        setSelectedPage = {setSelectedPage}
                    />
                    <NavLink
                        page="Our Classes"
                        selectedPage={selectedPage}
                        setSelectedPage = {setSelectedPage}
                    />
                    <NavLink
                        page="Contact Us"
                        selectedPage={selectedPage}
                        setSelectedPage = {setSelectedPage}
                    />
                </div>
            </div>
        )}
    </nav>
    );
}

export default Navbar