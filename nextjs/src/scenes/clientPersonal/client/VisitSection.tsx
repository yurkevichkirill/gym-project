'use client'

import { useEffect, useState } from "react";
import { observer } from "mobx-react-lite";
import Section, {
    emptyStateClassName,
    errorStateClassName,
    loadingStateClassName,
    primaryActionClassName,
    successStateClassName,
} from "@/shared/Section";
import ConfirmDialog from "@/shared/ui/ConfirmDialog";
import { useStore } from "@/store/StoreProvider";
import {
    formatMembershipDate,
    formatSessionLimit,
} from "@/scenes/clientPersonal/membership/membership-display";

const VisitSection = observer(() => {
    const { clientStore, membershipStore } = useStore();
    const [isConfirmOpen, setIsConfirmOpen] = useState(false);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);
    const membership = clientStore.activeMembership;
    const remainingVisits = membership?.sessionLimit === null
        ? null
        : membership
            ? Math.max(membership.sessionLimit - membership.visits, 0)
            : 0;
    const canRegisterVisit = membership !== null
        && (remainingVisits === null || remainingVisits > 0)
        && !clientStore.isVisiting;

    useEffect(() => {
        void clientStore.loadVisitOverview();
    }, [clientStore]);

    const registerVisit = async () => {
        setSuccessMessage(null);

        try {
            await clientStore.visit();
            await membershipStore.init();
            setIsConfirmOpen(false);
            setSuccessMessage("Visit registered. Membership data was refreshed from the server.");
        } catch {
            await membershipStore.init();
            setIsConfirmOpen(false);
        }
    };

    return (
        <Section title="Gym visit" titleId="gym-visit-title">
            <div className="flex flex-col gap-4">
                {clientStore.isVisitOverviewLoading && membership === null ? (
                    <div role="status" aria-live="polite" className={loadingStateClassName}>
                        Loading active membership...
                    </div>
                ) : null}

                {clientStore.visitOverviewError ? (
                    <div role="alert" className={errorStateClassName}>
                        <p className="font-semibold">Unable to load active membership.</p>
                        <p className="mt-1 text-sm">{clientStore.visitOverviewError}</p>
                        <button
                            type="button"
                            className={`${primaryActionClassName} mt-3`}
                            disabled={clientStore.isVisitOverviewLoading}
                            onClick={() => void clientStore.loadVisitOverview()}
                        >
                            {clientStore.isVisitOverviewLoading ? "Retrying..." : "Retry"}
                        </button>
                    </div>
                ) : null}

                {!clientStore.isVisitOverviewLoading
                && !clientStore.visitOverviewError
                && membership === null ? (
                    <div className={emptyStateClassName}>
                        <p className="font-semibold text-gray-900">No active membership</p>
                        <p className="mt-1">
                            Purchase or renew a membership before registering a gym visit.
                        </p>
                    </div>
                ) : null}

                {membership ? (
                    <div className="grid items-stretch gap-4 sm:grid-cols-2">
                        <div className="flex min-h-32 flex-col rounded-2xl border border-gray-100 bg-gray-20/70 p-4">
                            <p className="text-xs font-semibold uppercase text-gray-600">Active membership</p>
                            <p className="mt-2 text-xl font-bold text-gray-900">{membership.name}</p>
                            <p className="mt-auto pt-3 text-sm text-gray-600">
                                Ends {formatMembershipDate(membership.endDate)}
                            </p>
                        </div>
                        <div className="flex min-h-32 flex-col rounded-2xl border border-gray-100 bg-gray-20/70 p-4">
                            <p className="text-xs font-semibold uppercase text-gray-600">Visits remaining</p>
                            <p className="mt-2 text-xl font-bold text-gray-900">
                                {remainingVisits === null ? "Unlimited" : remainingVisits}
                            </p>
                            <p className="mt-auto pt-3 text-sm text-gray-600">
                                {membership.visits} used of {formatSessionLimit(membership.sessionLimit)}
                            </p>
                        </div>
                    </div>
                ) : null}

                {clientStore.visitError ? (
                    <div className={errorStateClassName} role="alert">
                        <p className="font-semibold">Unable to register visit.</p>
                        <p className="mt-1 text-sm">{clientStore.visitError}</p>
                    </div>
                ) : null}

                {successMessage ? (
                    <div className={successStateClassName} role="status" aria-live="polite">
                        <p className="font-semibold">Visit registered</p>
                        <p className="mt-1 text-sm">{successMessage}</p>
                    </div>
                ) : null}

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <button
                        type="button"
                        className={primaryActionClassName}
                        disabled={!canRegisterVisit || clientStore.isVisitOverviewLoading}
                        onClick={() => {
                            setSuccessMessage(null);
                            setIsConfirmOpen(true);
                        }}
                    >
                        {clientStore.isVisiting ? "Registering visit..." : "Register visit"}
                    </button>

                    {membership && remainingVisits === 0 ? (
                        <p className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            The session limit has been reached. Refresh the membership or renew it before registering another visit.
                        </p>
                    ) : null}
                </div>
            </div>

            <ConfirmDialog
                open={isConfirmOpen}
                title="Register gym visit?"
                description="The backend will consume one visit from the active membership. This action is not applied optimistically."
                confirmLabel="Register visit"
                isConfirming={clientStore.isVisiting}
                onConfirm={() => void registerVisit()}
                onCancel={() => setIsConfirmOpen(false)}
            />
        </Section>
    );
});

export default VisitSection;
