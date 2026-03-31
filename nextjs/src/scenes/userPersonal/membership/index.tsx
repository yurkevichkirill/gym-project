'use client'

import {useEffect, useState} from "react";
import MembershipType from "@/types/membership/membership.type";
import PersonalMembership from "@/scenes/userPersonal/membership/Membership";
import Section from "@/shared/Section";
import {getMyMemberships} from "@/api/memberships.api";

export const PersonalMemberships = () => {
    const [memberships, setMemberships] = useState<MembershipType[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const data = await getMyMemberships();
                setMemberships(data);
            } catch (e) {
                console.error(e);

                if (e instanceof Error) {
                    setError(e.message);
                } else {
                    setError("Something went wrong");
                }
            } finally {
                setLoading(false);
            }
        }

        void fetchData();
    }, []);

    if (loading) {
        return <div>Error: {error}</div>;
    }

    if (error) {
        return <div>Error: {error}</div>;
    }

    return (
        <Section title="My Memberships">
            <div className="grid md:grid-cols-2 gap-4">
                {...memberships.map((membership: MembershipType) => (
                    <PersonalMembership
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
        </Section>
    );
}

export default PersonalMemberships;