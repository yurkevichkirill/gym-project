'use client'

import PaymentType from "@/types/payment.type";
import Payment from "@/scenes/clientPersonal/payment/Payment";
import Section from "@/shared/Section";
import {useStore} from "@/store/StoreProvider";

export const Payments = () => {
    const { clientStore } = useStore();

    if (clientStore.isLoading) {
        return <div>Loading...</div>;
    }

    return (
        <Section title="Payments">
            <div className="flex flex-col gap-3">
                {clientStore.payments.map((payment: PaymentType) => (
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
}

export default Payments;