'use client'

import {useEffect, useState} from "react";
import {ApiResponse} from "@/types/api-response.type";
import MembershipType from "@/types/membership.type";
import PersonalMembership from "@/scenes/userPersonal/membership/Membership";
import Section from "@/shared/Section";

export const PersonalMemberships = () => {
    const [memberships, setMemberships] = useState<MembershipType[]>([]);

    useEffect(() => {
        const fetchData = async () => {
            const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/me/memberships/`, {
                credentials: "include",
            });
            if (!response.ok) {
                console.error("Failed to fetch personal memberships, status:  ", response.status);
            }
            const data: ApiResponse<MembershipType[]> = await response.json();

            setMemberships(data.data);
        }

        void fetchData();
    }, []);

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