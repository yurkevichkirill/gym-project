'use client'

import {useEffect} from "react";
import Client from "@/scenes/clientPersonal/client/Client";
import Bookings from "@/scenes/clientPersonal/bookings";
import PersonalMemberships from "@/scenes/clientPersonal/membership";
import Payments from "@/scenes/clientPersonal/payment";
import {useStore} from "@/store/StoreProvider";
import {observer} from "mobx-react-lite";
import {isClient} from "@/lib/utils/user.types.utils";

const MyPersonalClient = observer(() => {
    const { authStore, clientStore } = useStore();

    useEffect(() => {
        if (authStore.user && isClient(authStore.user)) {
            void clientStore.init();
        }
    }, [authStore.user]);

    if (authStore.isLoading || clientStore.isLoading) {
        return <div>Loading...</div>;
    }

    if (!authStore.user) {
        return <div>Not authorized</div>;
    }

    if (!isClient(authStore.user)) {
        return <div>Access denied</div>
    }

    return (
        <div className="pt-32 pb-20">
            <div className="mx-auto w-5/6 max-w-5xl flex flex-col gap-10">
                <Client />
                <Bookings />
                <PersonalMemberships />
                <Payments />
            </div>
        </div>
    );
});

export default MyPersonalClient;