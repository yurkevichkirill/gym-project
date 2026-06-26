'use client'

import Client from "@/scenes/clientPersonal/client/Client";
import Bookings from "@/scenes/clientPersonal/bookings";
import PersonalMemberships from "@/scenes/clientPersonal/membership";
import Payments from "@/scenes/clientPersonal/payment";
import {useStore} from "@/store/StoreProvider";
import {observer} from "mobx-react-lite";
import {useEffect} from "react";

const MyPersonalClient = observer(() => {
    const {clientStore} = useStore();

    useEffect(() => {
        if (
            clientStore.client === null
            && !clientStore.isLoading
            && clientStore.error === null
        ) {
            void clientStore.init();
        }
    }, [clientStore]);

    if (clientStore.isLoading && clientStore.client === null) {
        return <div className="pt-32 text-center">Loading profile...</div>;
    }

    if (clientStore.error !== null && clientStore.client === null) {
        return (
            <div className="pt-32 text-center">
                <p role="alert">{clientStore.error}</p>
                <button
                    type="button"
                    className="mt-4 rounded-md bg-secondary-500 px-5 py-2 disabled:opacity-50"
                    disabled={clientStore.isLoading}
                    onClick={() => void clientStore.init()}
                >
                    Retry
                </button>
            </div>
        );
    }

    if (clientStore.client === null) {
        return null;
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
