'use client'

import MembershipType from "@/types/membership/membership.type";
import PersonalMembership from "@/scenes/clientPersonal/membership/Membership";
import Section from "@/shared/Section";
import { useStore } from "@/store/StoreProvider";
import { observer } from "mobx-react-lite";
import { useEffect, useState, useMemo } from "react";
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

    if (membershipStore.isLoading) {
        return <div>Loading...</div>;
    }

    const visibleMemberships = isExpanded ? sortedMemberships : sortedMemberships.slice(0, 2);

    return (
        <Section title="My Memberships">
            <div className="grid md:grid-cols-2 gap-4">
                {visibleMemberships.map((membership: MembershipType) => (
                    <PersonalMembership
                        key={membership.id}
                        id={membership.id}
                        membershipPlan={membership.membershipPlan}
                        startDate={membership.startDate}
                        endDate={membership.endDate}
                        status={membership.status}
                        visits={membership.visits}
                        createdAt={membership.createdAt}
                    />
                ))}
            </div>

            {sortedMemberships.length > 2 && (
                <button
                    onClick={() => setIsExpanded(!isExpanded)}
                    className="flex items-center justify-center w-full gap-2 text-sm text-gray-500 hover:text-primary-500 py-2 mt-4 transition-colors cursor-pointer"
                >
                    {isExpanded ? "Show less" : `Show all (${sortedMemberships.length})`}
                    
                    <ChevronDownIcon
                        className={`w-4 h-4 transition-transform duration-300 ${
                            isExpanded ? "rotate-180" : ""
                        }`}
                    />
                </button>
            )}
        </Section>
    );
});

export default PersonalMemberships;