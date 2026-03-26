'use client'

import {useEffect, useState} from "react";
import {ApiResponse} from "@/types/api-response.type";
import MembershipType from "@/types/membership.type";
import PersonalMembership from "@/scenes/userPersonal/membership/Membership";

export const PersonalMemberships = () => {
    const [memberships, setMemberships] = useState<MembershipType[]>([]);

    useEffect(() => {
        const fetchData = async () => {
            const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/me/memberships/`);
            if (!response.ok) {
                console.error("Failed to fetch personal memberships, status:  ", response.status);
            }
            const data: ApiResponse<MembershipType[]> = await response.json();

            setMemberships(data.data);
        }

        void fetchData();
    }, []);

    return (
        <div className='p-20 border'>
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
    );
}

export default PersonalMemberships;