'use client'

import Link from "next/link";
import MembershipType from "@/types/membership/membership.type";
import PersonalMembership from "@/scenes/clientPersonal/membership/Membership";
import Section from "@/shared/Section";
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
        <Section title="My Memberships">
            <div className="flex flex-col gap-4">
                <div className="flex justify-end">
                    <Link
                        href="/me/memberships"
                        className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold transition hover:border-secondary-500"
                    >
                        View membership history
                    </Link>
                </div>

                {membershipStore.isLoading && !hasMemberships && (
                    <p className="text-sm text-gray-500">Loading memberships...</p>
                )}

                {membershipStore.error && (
                    <div className="rounded-md border border-primary-500 bg-red-50 p-4" role="alert">
                        <p className="font-semibold">Unable to load memberships.</p>
                        <p className="mt-1 text-sm text-gray-600">{membershipStore.error}</p>
                        <button
                            type="button"
                            onClick={() => void membershipStore.init()}
                            disabled={membershipStore.isLoading || membershipStore.isRefreshing}
                            className="mt-3 cursor-pointer rounded-md bg-secondary-500 px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {membershipStore.isLoading || membershipStore.isRefreshing ? "Retrying..." : "Retry"}
                        </button>
                    </div>
                )}

                {!membershipStore.isLoading
                    && !membershipStore.isRefreshing
                    && !membershipStore.error
                    && !hasMemberships && (
                    <p className="text-sm text-gray-500">You have no memberships yet.</p>
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
                    <p className="text-sm text-gray-500">Refreshing memberships...</p>
                )}

                {sortedMemberships.length > 2 && (
                    <button
                        type="button"
                        onClick={() => setIsExpanded(!isExpanded)}
                        className="mt-4 flex w-full cursor-pointer items-center justify-center gap-2 py-2 text-sm text-gray-500 transition-colors hover:text-primary-500"
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
