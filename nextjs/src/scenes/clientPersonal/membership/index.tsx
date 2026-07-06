'use client'

import Link from "next/link";
import MembershipType from "@/types/membership/membership.type";
import PersonalMembership from "@/scenes/clientPersonal/membership/Membership";
import Section, {
    emptyStateClassName,
    errorStateClassName,
    loadingStateClassName,
    primaryActionClassName,
    secondaryActionClassName,
} from "@/shared/Section";
import { useStore } from "@/store/StoreProvider";
import { observer } from "mobx-react-lite";
import { useEffect, useMemo, useState } from "react";
import { ChevronDownIcon } from "@heroicons/react/24/outline";
import { MembershipStatusEnum } from "@/types/membership/membership-status.enum";

const statusWeights: Record<string, number> = {
    [MembershipStatusEnum.ACTIVE]: 1,
    [MembershipStatusEnum.FROZEN]: 2,
    [MembershipStatusEnum.PENDING]: 3,
    [MembershipStatusEnum.EXPIRED]: 4,
    [MembershipStatusEnum.CANCELED_BY_CLIENT]: 5,
    [MembershipStatusEnum.CANCELED_BY_SYSTEM]: 6,
    [MembershipStatusEnum.CANCELED_PAYMENT_FAILED]: 7,
};

export const PersonalMemberships = observer(() => {
    const { membershipStore } = useStore();
    const [isExpanded, setIsExpanded] = useState(false);

    useEffect(() => {
        void membershipStore.init();
    }, [membershipStore]);

    const sortedMemberships = useMemo(() => {
        return [...membershipStore.memberships].sort((a, b) => {
            const weightA = statusWeights[a.status] || 99;
            const weightB = statusWeights[b.status] || 99;

            return weightA - weightB;
        });
    }, [membershipStore.memberships]);

    const hasMemberships = sortedMemberships.length > 0;
    const visibleMemberships = isExpanded ? sortedMemberships : sortedMemberships.slice(0, 2);

    return (
        <Section
            title="My Memberships"
            titleId="my-memberships-title"
            action={(
                <Link
                    href="/me/memberships"
                    className={secondaryActionClassName}
                    aria-label="View membership history"
                >
                    View membership history
                </Link>
            )}
        >
            <div className="flex flex-col gap-4">

                {membershipStore.isLoading && !hasMemberships && (
                    <div role="status" aria-live="polite" className={loadingStateClassName}>Loading memberships...</div>
                )}

                {membershipStore.error && (
                    <div className={errorStateClassName} role="alert">
                        <p className="font-semibold">Unable to load memberships.</p>
                        <p className="mt-1 text-sm">{membershipStore.error}</p>
                        <button
                            type="button"
                            onClick={() => void membershipStore.init()}
                            disabled={membershipStore.isLoading || membershipStore.isRefreshing}
                            className={`${primaryActionClassName} mt-3`}
                        >
                            {membershipStore.isLoading || membershipStore.isRefreshing ? "Retrying..." : "Retry"}
                        </button>
                    </div>
                )}

                {!membershipStore.isLoading
                    && !membershipStore.isRefreshing
                    && !membershipStore.error
                    && !hasMemberships && (
                    <div className={emptyStateClassName}>You have no memberships yet.</div>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                    {visibleMemberships.map((membership: MembershipType) => (
                        <PersonalMembership
                            key={membership.id}
                            id={membership.id}
                            name={membership.name}
                            sessionLimit={membership.sessionLimit}
                            membershipPlan={membership.membershipPlan}
                            startDate={membership.startDate}
                            endDate={membership.endDate}
                            status={membership.status}
                            visits={membership.visits}
                        />
                    ))}
                </div>

                {membershipStore.isRefreshing && hasMemberships && (
                    <p className="text-sm text-gray-600" role="status" aria-live="polite">Refreshing memberships...</p>
                )}

                {sortedMemberships.length > 2 && (
                    <button
                        type="button"
                        onClick={() => setIsExpanded(!isExpanded)}
                        className="mt-2 flex min-h-10 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-gray-100 bg-white px-4 py-2 text-sm font-semibold text-gray-500 transition hover:border-primary-300 hover:bg-primary-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                    >
                        {isExpanded ? "Show less" : `Show all (${sortedMemberships.length})`}

                        <ChevronDownIcon
                            className={`h-4 w-4 transition-transform duration-300 ${
                                isExpanded ? "rotate-180" : ""
                            }`}
                        />
                    </button>
                )}
            </div>
        </Section>
    );
});

export default PersonalMemberships;
