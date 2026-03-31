'use client'

import {useEffect, useState} from "react";
import UserPersonalType from "@/types/user/user.type";
import User from "@/scenes/userPersonal/user/User";
import Bookings from "@/scenes/userPersonal/bookings";
import PersonalMemberships from "@/scenes/userPersonal/membership";
import Payments from "@/scenes/userPersonal/payment";
import {getMe} from "@/api/user.api";

const MyPersonalUser = () => {
    const [user, setUser] = useState<UserPersonalType>();
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const data = await getMe();
                setUser(data);
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
        return <div>Loading...</div>;
    }

    if (error) {
        return <div>Error: {error}</div>;
    }

    if (!user) {
        return null;
    }

    return (
        <div className="pt-32 pb-20">
            <div className="mx-auto w-5/6 max-w-5xl flex flex-col gap-10">
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
            </div>
        </div>
    );
}

export default MyPersonalUser;