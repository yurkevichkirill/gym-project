'use client'

import MembershipType from "@/types/membership/membership.type";
import PersonalMembership from "@/scenes/clientPersonal/membership/Membership";
import Section from "@/shared/Section";
import {useStore} from "@/store/StoreProvider";

export const PersonalMemberships = () => {
    const { clientStore } = useStore();

    if (clientStore.isLoading) {
        return <div>Loading...</div>;
    }

    return (
        <Section title="My Memberships">
            <div className="grid md:grid-cols-2 gap-4">
                {clientStore.memberships.map((membership: MembershipType) => (
                    <PersonalMembership
                        key = {membership.id}
                        id = {membership.id}
                        membershipPlan = {membership.membershipPlan}
                        startDate = {membership.startDate}
                        endDate = {membership.endDate}
                        status = {membership.status}
                        visits = {membership.visits}
                        createdAt = {membership.createdAt}
                    />
                ))}
            </div>
        </Section>
    );
}

export default PersonalMemberships;