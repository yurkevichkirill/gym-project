'use client'

import { useEffect, useState } from "react";
import { observer } from "mobx-react-lite";
import Section from "@/shared/Section";
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
        <Section title="Gym visit">
            <div className="rounded-2xl border border-gray-100 p-5">
                {clientStore.isVisitOverviewLoading && membership === null ? (
                    <p role="status" className="text-sm text-gray-500">
                        Loading active membership...
                    </p>
                ) : null}

                {clientStore.visitOverviewError ? (
                    <div role="alert" className="rounded-xl bg-red-50 p-4 text-red-700">
                        <p>{clientStore.visitOverviewError}</p>
                        <button
                            type="button"
                            className="mt-3 rounded-md border border-red-300 bg-white px-4 py-2 font-semibold disabled:opacity-50"
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
                    <div>
                        <p className="font-semibold">No active membership</p>
                        <p className="mt-1 text-sm text-gray-600">
                            Purchase or renew a membership before registering a gym visit.
                        </p>
                    </div>
                ) : null}

                {membership ? (
                    <div className="grid gap-5 sm:grid-cols-2">
                        <div>
                            <p className="text-sm text-gray-500">Active membership</p>
                            <p className="mt-1 text-xl font-bold">{membership.name}</p>
                            <p className="mt-2 text-sm text-gray-600">
                                Ends {formatMembershipDate(membership.endDate)}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">Visits</p>
                            <p className="mt-1 text-xl font-bold">
                                {remainingVisits === null ? "Unlimited" : `${remainingVisits} remaining`}
                            </p>
                            <p className="mt-2 text-sm text-gray-600">
                                {membership.visits} used of {formatSessionLimit(membership.sessionLimit)}
                            </p>
                        </div>
                    </div>
                ) : null}

                {clientStore.visitError ? (
                    <p className="mt-4 rounded-xl bg-red-50 p-4 text-red-700" role="alert">
                        {clientStore.visitError}
                    </p>
                ) : null}

                {successMessage ? (
                    <p className="mt-4 rounded-xl bg-emerald-50 p-4 text-emerald-800" role="status">
                        {successMessage}
                    </p>
                ) : null}

                <button
                    type="button"
                    className="mt-5 rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                    disabled={!canRegisterVisit || clientStore.isVisitOverviewLoading}
                    onClick={() => {
                        setSuccessMessage(null);
                        setIsConfirmOpen(true);
                    }}
                >
                    {clientStore.isVisiting ? "Registering visit..." : "Register visit"}
                </button>

                {membership && remainingVisits === 0 ? (
                    <p className="mt-3 text-sm text-amber-700">
                        The session limit has been reached. Refresh the membership or renew it before registering another visit.
                    </p>
                ) : null}
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
