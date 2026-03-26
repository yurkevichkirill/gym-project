'use client'

import {useEffect, useState} from "react";
import UserPersonalType from "@/types/user.type";
import {ApiResponse} from "@/types/api-response.type";
import User from "@/scenes/userPersonal/user/User";
import Bookings from "@/scenes/userPersonal/bookings";
import PersonalMemberships from "@/scenes/userPersonal/membership";
import Payments from "@/scenes/userPersonal/payment";

const MyPersonalUser = () => {

    const [user, setUser] = useState<UserPersonalType>();

    useEffect(() => {
        const fetchData = async () => {
            const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/me`);
            if (!response.ok) {
                console.error("Failed to fetch user, status:  ", response.status);
            }
            const data: ApiResponse<UserPersonalType> = await response.json();
            setUser(data.data);
        }
        void fetchData();
    }, []);

    if (!user) return null;

    return (
        <>
            <User
                id={user.id}
                age={user.age}
                firstName={user.firstName}
                lastName={user.lastName}
                email={user.email}
                phone={user.phone}
                createdAt={user.createdAt}
                balance={user.balance}
            />
            <Bookings />
            <PersonalMemberships />
            <Payments />
        </>
    );
}

export default MyPersonalUser;