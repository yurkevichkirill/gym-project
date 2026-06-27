'use client'

import { useEffect } from "react";
import Link from "next/link";
import { observer } from "mobx-react-lite";
import { useStore } from "@/store/StoreProvider";
import EmptyState from "@/shared/ui/EmptyState";
import ErrorState from "@/shared/ui/ErrorState";
import LoadingState from "@/shared/ui/LoadingState";
import MembershipActions from "@/scenes/clientPersonal/membership/MembershipActions";
import {
    formatEnumLabel,
    formatMembershipDate,
    formatMembershipDateTime,
    formatMembershipMoney,
    formatSessionLimit,
    getMembershipStatusClassName,
    getMembershipStatusLabel,
} from "@/scenes/clientPersonal/membership/membership-display";

type MembershipDetailsProps = {
    membershipId: number;
};

const DetailRow = ({ label, value }: { label: string; value: string }) => (
    <div className="flex flex-col gap-1 border-b border-gray-100 py-3 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
        <dt className="text-sm text-gray-500">{label}</dt>
        <dd className="font-semibold capitalize sm:text-right">{value}</dd>
    </div>
);

const MembershipDetails = observer(({ membershipId }: MembershipDetailsProps) => {
    const { membershipStore } = useStore();
    const membership = membershipStore.selectedMembership?.id === membershipId
        ? membershipStore.selectedMembership
        : null;

    useEffect(() => {
        void membershipStore.loadMembership(membershipId);
    }, [membershipId, membershipStore]);

    if (membership === null && membershipStore.isDetailLoading) {
        return (
            <LoadingState
                title="Loading membership..."
                description="We are fetching the latest membership details."
            />
        );
    }

    if (membership === null && membershipStore.detailErrorStatus === 404) {
        return (
            <EmptyState
                title="Membership not found"
                description="This membership does not exist or is no longer available."
                action={(
                    <Link href="/me/memberships" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold">
                        Back to memberships
                    </Link>
                )}
            />
        );
    }

    if (membership === null && membershipStore.detailErrorStatus === 403) {
        return (
            <EmptyState
                title="Access denied"
                description="You cannot view a membership that belongs to another client."
                action={(
                    <Link href="/me/memberships" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold">
                        Back to memberships
                    </Link>
                )}
            />
        );
    }

    if (membership === null && membershipStore.detailError) {
        return (
            <ErrorState
                title="Unable to load membership"
                message={membershipStore.detailError}
                isRetrying={membershipStore.isDetailLoading}
                onRetry={() => void membershipStore.loadMembership(membershipId)}
            />
        );
    }

    if (membership === null) {
        return <LoadingState title="Loading membership..." />;
    }

    const plan = membership.membershipPlan;
    const payment = membership.payment;

    return (
        <section className="mx-auto w-full max-w-5xl" aria-busy={membershipStore.isDetailLoading}>
            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <Link
                    href="/me/memberships"
                    className="rounded-md border border-gray-300 bg-white px-4 py-2 font-semibold transition hover:border-secondary-500"
                >
                    Back to memberships
                </Link>
                {membershipStore.isDetailLoading ? (
                    <p role="status" aria-live="polite" className="text-sm font-semibold text-secondary-500">
                        Refreshing membership...
                    </p>
                ) : null}
            </div>

            {membershipStore.detailError ? (
                <div
                    role="alert"
                    className="mb-6 flex flex-col gap-3 rounded-xl bg-red-50 p-4 text-red-700 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p>{membershipStore.detailError}</p>
                    <button
                        type="button"
                        className="self-start rounded-md border border-red-300 bg-white px-4 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-50 sm:self-auto"
                        disabled={membershipStore.isDetailLoading}
                        onClick={() => void membershipStore.loadMembership(membershipId)}
                    >
                        {membershipStore.isDetailLoading ? "Retrying..." : "Retry"}
                    </button>
                </div>
            ) : null}

            <article className="rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                <div className="flex flex-wrap items-start justify-between gap-5">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                            Membership #{membership.id}
                        </p>
                        <h1 className="mt-2 text-3xl font-bold">{membership.name}</h1>
                        <p className="mt-2 text-gray-600">
                            {formatMembershipDate(membership.startDate, "Not started")}
                            {" — "}
                            {formatMembershipDate(membership.endDate)}
                        </p>
                    </div>
                    <span className={`rounded-full px-4 py-2 text-sm font-semibold ${getMembershipStatusClassName(membership.status)}`}>
                        {getMembershipStatusLabel(membership.status)}
                    </span>
                </div>

                <MembershipActions
                    membershipId={membership.id}
                    status={membership.status}
                    className="mt-5"
                />

                <div className="mt-8 grid gap-6 lg:grid-cols-2">
                    <section className="rounded-xl border border-gray-100 p-5">
                        <h2 className="text-xl font-bold">Membership snapshot</h2>
                        <dl className="mt-3">
                            <DetailRow label="Name" value={membership.name} />
                            <DetailRow label="Duration" value={`${membership.durationDays} days`} />
                            <DetailRow label="Session limit" value={formatSessionLimit(membership.sessionLimit)} />
                            <DetailRow label="Visits used" value={membership.visits.toString()} />
                            <DetailRow label="Start date" value={formatMembershipDate(membership.startDate, "Not started")} />
                            <DetailRow label="End date" value={formatMembershipDate(membership.endDate)} />
                            <DetailRow label="Created at" value={formatMembershipDateTime(membership.createdAt)} />
                            <DetailRow label="Frozen at" value={formatMembershipDateTime(membership.frozenAt)} />
                        </dl>
                    </section>

                    <section className="rounded-xl border border-gray-100 p-5">
                        <h2 className="text-xl font-bold">Linked plan</h2>
                        <dl className="mt-3">
                            <DetailRow label="Availability" value={plan === null ? "Unavailable" : "Available"} />
                            <DetailRow label="Plan ID" value={plan?.id.toString() ?? "Not available"} />
                            <DetailRow label="Current name" value={plan?.name ?? "Not available"} />
                            <DetailRow label="Current duration" value={plan === null ? "Not available" : `${plan.durationDays} days`} />
                            <DetailRow label="Current session limit" value={plan === null ? "Not available" : formatSessionLimit(plan.sessionLimit)} />
                        </dl>
                        {plan === null ? (
                            <p className="mt-4 text-sm text-gray-500">
                                Snapshot values above remain available even when the linked membership plan is no longer returned by the API.
                            </p>
                        ) : null}
                    </section>

                    <section className="rounded-xl border border-gray-100 p-5 lg:col-span-2">
                        <h2 className="text-xl font-bold">Payment</h2>
                        <dl className="mt-3 grid gap-x-8 md:grid-cols-2">
                            <DetailRow label="Payment ID" value={payment.id.toString()} />
                            <DetailRow label="Amount" value={formatMembershipMoney(payment.amount, payment.currency)} />
                            <DetailRow label="Method" value={formatEnumLabel(payment.method)} />
                            <DetailRow label="Category" value={formatEnumLabel(payment.category)} />
                            <DetailRow label="Status" value={formatEnumLabel(payment.status)} />
                            <DetailRow label="Refund" value={payment.isRefund ? "Yes" : "No"} />
                            <DetailRow label="Created at" value={formatMembershipDateTime(payment.createdAt)} />
                            <DetailRow label="Paid at" value={formatMembershipDateTime(payment.paidAt)} />
                            <DetailRow label="Expires at" value={formatMembershipDateTime(payment.expiresAt)} />
                        </dl>
                    </section>
                </div>
            </article>
        </section>
    );
});

export default MembershipDetails;
