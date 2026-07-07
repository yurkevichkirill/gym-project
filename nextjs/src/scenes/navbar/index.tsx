'use client'

import { useNavigation } from "@/context/navigation-context";
import { useEffect, useState } from "react";
import Image from "next/image"
import useMediaQuery from "@/hooks/useMediaQuery";
import Logo from "@/assets/Logo.png"
import NavLink from "@/scenes/navbar/NavLink";
import { Bars3Icon, XMarkIcon } from "@heroicons/react/24/solid";
import { useStore } from "@/store/StoreProvider";
import Link from "next/link";
import { observer } from "mobx-react-lite";
import { usePathname, useRouter } from "next/navigation";
import { notify } from "@/lib/notify";
import dynamic from "next/dynamic";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { getAccountPath } from "@/lib/utils/user.types.utils";
import ConfirmDialog from "@/shared/ui/ConfirmDialog";

const LoginModal = dynamic(() => import("@/scenes/authorization"), {
    ssr: false
});
const RegisterModal = dynamic(() => import("../registration"), {
    ssr: false
});

const Navbar = observer(() => {
    const { selectedPage, setSelectedPage, isTopOfPage } = useNavigation();
    const router = useRouter();
    const path = usePathname();
    const isHome = path === '/';

    const flexBetween = "flex items-center justify-between";
    const [isMenuToggled, setIsMenuToggled] = useState<boolean>(false);
    const isAboveMediumScreens = useMediaQuery("(min-width: 1250px)");
    const navbarBackground = isTopOfPage ? "bg-gray-20" : "bg-primary-100 drop-shadow";

    const [isLoginOpen, setIsLoginOpen] = useState(false);
    const [isRegisterOpen, setIsRegisterOpen] = useState(false);
    const [isLogoutConfirmOpen, setIsLogoutConfirmOpen] = useState(false);

    const { authStore } = useStore();
    const accountPath = authStore.user ? getAccountPath(authStore.user) : null;

    const handleLogout = async () => {
        const toastId = notify.loading("Logging out...");

        try {
            await authStore.logout();
            setIsLogoutConfirmOpen(false);
            router.push("/");
            notify.success("Logged out", "Log in to continue", toastId);
        } catch (error: unknown) {
            notify.error("Logout failed", getErrorMessage(error), toastId);
        }
    };

    useEffect(() => {
        if (!isLoginOpen && !isRegisterOpen) return;

        const scrollBarWidth = window.innerWidth - document.documentElement.clientWidth;

        document.body.style.overflow = "hidden";
        document.body.style.paddingRight = `${scrollBarWidth}px`;

        return () => {
            document.body.style.overflow = "";
            document.body.style.paddingRight = "";
        };
    }, [isLoginOpen, isRegisterOpen]);

    useEffect(() => {
        if (path !== "/") return;

        const searchParams = new URLSearchParams(window.location.search);

        if (searchParams.get("login") !== "required") return;

        setIsLoginOpen(true);
        searchParams.delete("login");

        const query = searchParams.toString();
        router.replace(query ? `/?${query}` : "/", { scroll: false });
    }, [path, router]);

    return (
    <nav>
        <div className={`${navbarBackground} ${flexBetween} fixed top-0 z-30 w-full py-6`}>
            <div className={`${flexBetween} mx-auto w-5/6`}>
                <div className={`${flexBetween} w-full gap-16`}>
                    {/*LEFT SIDE*/}
                    <Link href="/" className="shrink-0">
                        <Image src={Logo} alt="logo"/>
                    </Link>

                    {/*RIGHT SIDE*/}
                    {isAboveMediumScreens ? (
                        <div className={`${flexBetween} w-full`}>
                            <div className={`${flexBetween} gap-8 text-sm`}>
                                {isHome &&
                                <>
                                    <NavLink page="Home" selectedPage={selectedPage} setSelectedPage={setSelectedPage} />
                                    <NavLink page="Our Trainers" selectedPage={selectedPage} setSelectedPage={setSelectedPage} />
                                    <NavLink page="Memberships" selectedPage={selectedPage} setSelectedPage={setSelectedPage} />
                                    <NavLink page="Training Types" selectedPage={selectedPage} setSelectedPage={setSelectedPage} />
                                </>
                                }
                            </div>
                            {authStore.isAuth ?
                                <div className={`${flexBetween} gap-8`}>
                                    <p>{authStore.user?.email}</p>
                                    {accountPath ? (
                                        <Link
                                            className="rounded-md cursor-pointer bg-secondary-500 px-10 py-2 hover:bg-primary-500 hover:text-white"
                                            href={accountPath}
                                        >
                                            My Cabinet
                                        </Link>
                                    ) : null}
                                    <button
                                        className="hover:text-primary-500 cursor-pointer"
                                        type="button"
                                        onClick={() => setIsLogoutConfirmOpen(true)}
                                    >
                                        Logout
                                    </button>
                                </div> :
                                <div className={`${flexBetween} gap-8`}>
                                    <button className="hover:text-secondary-500 cursor-pointer" type="button" onClick={() => setIsLoginOpen(true)}>
                                        Sign In
                                    </button>

                                    <button
                                        className="rounded-md cursor-pointer bg-secondary-500 px-10 py-2 hover:bg-primary-500 hover:text-white transition duration-300"
                                        type="button"
                                        onClick={() => setIsRegisterOpen(true)}
                                    >
                                        Become a member
                                    </button>
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
                            {accountPath ? (
                                <Link
                                    className="cursor-pointer text-left transition duration-500 hover:text-primary-300"
                                    href={accountPath}
                                    onClick={() => setIsMenuToggled(false)}
                                >
                                    My Cabinet
                                </Link>
                            ) : null}
                            <button
                                className="cursor-pointer text-left transition duration-500 hover:text-primary-300"
                                type="button"
                                onClick={() => { setIsLogoutConfirmOpen(true); setIsMenuToggled(false); }}
                            >
                                Logout
                            </button>
                        </>
                        :
                        <>
                            <button
                                className="cursor-pointer text-left transition duration-500 hover:text-primary-300"
                                type="button"
                                onClick={() => { setIsLoginOpen(true); setIsMenuToggled(false); }}
                            >
                                Sign In
                            </button>
                            <button
                                className="cursor-pointer text-left transition duration-500 text-secondary-500 font-bold hover:text-primary-300"
                                type="button"
                                onClick={() => { setIsRegisterOpen(true); setIsMenuToggled(false); }}
                            >
                                Become a member
                            </button>
                        </>
                    }

                    <NavLink page="Home" selectedPage={selectedPage} setSelectedPage={setSelectedPage} />
                    <NavLink page="Our Trainers" selectedPage={selectedPage} setSelectedPage={setSelectedPage} />
                    <NavLink page="Memberships" selectedPage={selectedPage} setSelectedPage={setSelectedPage} />
                    <NavLink page="Training Types" selectedPage={selectedPage} setSelectedPage={setSelectedPage} />
                </div>
            </div>
        )}

        <LoginModal isOpen={isLoginOpen} onClose={() => setIsLoginOpen(false)} />
        <RegisterModal
            isOpen={isRegisterOpen}
            onClose={() => setIsRegisterOpen(false)}
            onSwitchToLogin={() => setIsLoginOpen(true)}
        />
        <ConfirmDialog
            open={isLogoutConfirmOpen}
            title="Log out?"
            description="You will leave your current session and return to the public site."
            confirmLabel="Log out"
            cancelLabel="Stay signed in"
            isConfirming={authStore.isLoading}
            onConfirm={() => void handleLogout()}
            onCancel={() => {
                if (!authStore.isLoading) {
                    setIsLogoutConfirmOpen(false);
                }
            }}
        />
    </nav>
    );
});

export default Navbar;
