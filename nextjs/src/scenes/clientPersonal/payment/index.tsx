'use client'

import PaymentType from "@/types/payment/payment.type";
import Payment from "@/scenes/clientPersonal/payment/Payment";
import Section from "@/shared/Section";
import {useStore} from "@/store/StoreProvider";
import { observer } from "mobx-react-lite";
import { useEffect } from "react";

export const Payments = observer(() => {
    const { paymentStore } = useStore();

    useEffect(() => {
        void paymentStore.init();
    }, [paymentStore]);

    if (paymentStore.isLoading) {
        return <div>Loading...</div>;
    }

    return (
        <Section title="Payments">
            <div className="flex flex-col gap-3">
                {paymentStore.payments.map((payment: PaymentType) => (
                    <Payment
                        key = {payment.id}
                        id = {payment.id}
                        trainer = {payment.trainer}
                        amount = {payment.amount}
                        category = {payment.category}
                        isRefund = {payment.isRefund}
                    />
                ))}
            </div>
        </Section>
    );
});

export default Payments;