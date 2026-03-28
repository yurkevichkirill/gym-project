'use client'

import {useEffect, useState} from "react";
import {ApiResponse} from "@/types/api-response.type";
import PaymentType from "@/types/payment.type";
import Payment from "@/scenes/userPersonal/payment/Payment";
import Section from "@/shared/Section";
import {getMyMemberships} from "@/api/memberships.api";
import {getMyPayments} from "@/api/payments.api";

export const Payments = () => {
    const [payments, setPayments] = useState<PaymentType[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const data = await getMyPayments();
                setPayments(data);
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
        <Section title="Payments">
            <div className="flex flex-col gap-3">
                {...payments.map((payment: PaymentType) => (
                    <Payment
                        id={payment.id}
                        trainer={payment.trainer}
                        amount={payment.amount}
                        category={payment.category}
                    />
                ))}
            </div>
        </Section>
    );
}

export default Payments;