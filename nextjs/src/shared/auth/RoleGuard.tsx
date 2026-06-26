'use client'

import Link from "next/link";
import type {ReactNode} from "react";
import {observer} from "mobx-react-lite";
import {AuthStatus} from "@/store/AuthStore";
import {useStore} from "@/store/StoreProvider";
import type {AccountRole} from "@/types/auth.type";
import {getAccountPath, hasRole} from "@/lib/utils/user.types.utils";
import ErrorState from "@/shared/ui/ErrorState";
import LoadingState from "@/shared/ui/LoadingState";

type RoleGuardProps = {
    allowedRoles: readonly AccountRole[];
    children: ReactNode;
};

type AccessFallbackProps = {
    title: string;
    description: string;
    href: string;
    actionLabel: string;
};

const AccessFallback = ({
    title,
    description,
    href,
    actionLabel,
}: AccessFallbackProps) => {
    return (
        <main className="px-6 pt-32 pb-20">
            <section className="mx-auto flex min-h-60 w-full max-w-3xl flex-col items-center justify-center rounded-2xl bg-white px-6 py-10 text-center shadow-sm">
                <h1 className="text-2xl font-bold">{title}</h1>
                <p className="mt-3 max-w-xl text-gray-500">{description}</p>
                <Link
                    href={href}
                    className="mt-6 rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white"
                >
                    {actionLabel}
                </Link>
            </section>
        </main>
    );
};

const RoleGuard = observer(({allowedRoles, children}: RoleGuardProps) => {
    const {authStore} = useStore();
    const user = authStore.user;
    const isCheckingSession = authStore.status === AuthStatus.INITIAL
        || (authStore.status === AuthStatus.LOADING && user === null);

    if (isCheckingSession) {
        return (
            <main className="px-6 pt-32 pb-20">
                <LoadingState
                    title="Checking access..."
                    description="We are verifying your session and account role."
                />
            </main>
        );
    }

    if (authStore.status === AuthStatus.ERROR) {
        return (
            <main className="px-6 pt-32 pb-20">
                <ErrorState
                    title="Unable to verify access"
                    message={authStore.error ?? "Your session could not be verified."}
                    isRetrying={authStore.isLoading}
                    onRetry={() => {
                        void authStore.checkAuth();
                    }}
                />
            </main>
        );
    }

    if (authStore.status === AuthStatus.UNAUTHENTICATED || user === null) {
        return (
            <AccessFallback
                title="Authentication required"
                description="Sign in to open this protected area."
                href="/?login=required"
                actionLabel="Sign in"
            />
        );
    }

    const isAllowed = allowedRoles.some((role) => hasRole(user, role));

    if (!isAllowed) {
        const accountPath = getAccountPath(user);

        return (
            <AccessFallback
                title="Access denied"
                description="Your account does not have permission to open this area."
                href={accountPath ?? "/"}
                actionLabel={accountPath ? "Go to my cabinet" : "Back to home"}
            />
        );
    }

    return children;
});

export default RoleGuard;
