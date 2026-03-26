'use client'

import {useEffect, useState} from "react";
import {ApiResponse} from "@/types/api-response.type";
import PaymentType from "@/types/payment.type";
import Payment from "@/scenes/userPersonal/payment/Payment";

export const Payments = () => {
    const [payments, setPayments] = useState<PaymentType[]>([]);

    useEffect(() => {
        const fetchData = async () => {
            const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/me/payments/`);
            if (!response.ok) {
                console.error("Failed to fetch payments, status:  ", response.status);
            }
            const data: ApiResponse<PaymentType[]> = await response.json();

            setPayments(data.data);
        }

        void fetchData();
    }, []);

    return (
        <div className='p-20 border'>
            {...payments.map((payment: PaymentType) => (
                <Payment
                    id={payment.id}
                    trainer={payment.trainer}
                    amount={payment.amount}
                    category={payment.category}
                />
            ))}
        </div>
    );
}

export default Payments;