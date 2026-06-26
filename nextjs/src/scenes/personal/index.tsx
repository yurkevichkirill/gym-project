'use client'

import {observer} from "mobx-react-lite";
import {useEffect} from "react";
import {useRouter} from "next/navigation";
import MyPersonalClient from "@/scenes/clientPersonal/client";
import {AuthStatus} from "@/store/AuthStore";
import {useStore} from "@/store/StoreProvider";
import {getAccountRole} from "@/lib/utils/user.types.utils";
import {CurrentUser, Roles} from "@/types/auth.type";

const RoleCabinet = ({
    title,
    user,
}: {
    title: string;
    user: CurrentUser;
}) => {
    return (
        <div className="pt-32 pb-20">
            <section className="mx-auto w-5/6 max-w-3xl rounded-2xl bg-white p-8 shadow-md">
                <h1 className="text-3xl font-bold">{title}</h1>
                <dl className="mt-6 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt className="text-sm text-gray-500">User ID</dt>
                        <dd className="font-semibold">{user.id}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-gray-500">Email</dt>
                        <dd className="font-semibold">{user.email}</dd>
                    </div>
                    <div className="sm:col-span-2">
                        <dt className="text-sm text-gray-500">Roles</dt>
                        <dd className="font-semibold">{user.roles.join(", ")}</dd>
                    </div>
                </dl>
            </section>
        </div>
    );
};

const PersonalAccount = observer(() => {
    const {authStore} = useStore();
    const router = useRouter();

    useEffect(() => {
        if (authStore.status === AuthStatus.UNAUTHENTICATED) {
            router.replace("/?login=required");
        }
    }, [authStore.status, router]);

    if (
        authStore.status === AuthStatus.INITIAL
        || (authStore.status === AuthStatus.LOADING && authStore.user === null)
    ) {
        return <div className="pt-32 text-center">Loading...</div>;
    }

    if (authStore.status === AuthStatus.ERROR) {
        return (
            <div className="pt-32 text-center">
                <p role="alert">{authStore.error ?? "Unable to verify your session."}</p>
                <button
                    type="button"
                    className="mt-4 rounded-md bg-secondary-500 px-5 py-2 disabled:opacity-50"
                    disabled={authStore.isLoading}
                    onClick={() => void authStore.checkAuth()}
                >
                    Retry
                </button>
            </div>
        );
    }

    if (authStore.user === null) {
        return <div className="pt-32 text-center">Redirecting...</div>;
    }

    const accountRole = getAccountRole(authStore.user);

    if (accountRole === Roles.CLIENT) {
        return <MyPersonalClient />;
    }

    if (accountRole === Roles.TRAINER) {
        return <RoleCabinet title="Trainer cabinet" user={authStore.user} />;
    }

    if (accountRole === Roles.ADMIN) {
        return <RoleCabinet title="Admin cabinet" user={authStore.user} />;
    }

    return <div className="pt-32 text-center">Access denied</div>;
});

export default PersonalAccount;
