'use client'

import PaymentType from "@/types/payment/payment.type";
import Payment from "@/scenes/clientPersonal/payment/Payment";
import Section from "@/shared/Section";
import { useStore } from "@/store/StoreProvider";
import { observer } from "mobx-react-lite";
import { useEffect, useState } from "react";
import { ChevronDownIcon } from "@heroicons/react/24/outline";

export const Payments = observer(() => {
    const { paymentStore } = useStore();
    const [isExpanded, setIsExpanded] = useState(false);

    useEffect(() => {
        void paymentStore.init();
    }, [paymentStore]);

    if (paymentStore.isLoading) {
        return <div>Loading...</div>;
    }

    const visiblePayments = isExpanded ? paymentStore.payments : paymentStore.payments.slice(0, 3);

    return (
        <Section title="Payments">
            <div className="flex flex-col gap-3">
                {visiblePayments.map((payment: PaymentType) => (
                    <Payment
                        key={payment.id}
                        id={payment.id}
                        trainer={payment.trainer}
                        amount={payment.amount}
                        currency={payment.currency}
                        category={payment.category}
                        status={payment.status}
                        method={payment.method}
                        isRefund={payment.isRefund}
                        createdAt={payment.createdAt}
                    />
                ))}
            </div>

            {paymentStore.payments.length > 3 && (
                <button
                    onClick={() => setIsExpanded(!isExpanded)}
                    className="flex items-center justify-center w-full gap-2 text-sm text-gray-500 hover:text-primary-500 py-2 mt-2 transition-colors cursor-pointer"
                >
                    {isExpanded ? "Show less" : `Show all (${paymentStore.payments.length})`}
                    <ChevronDownIcon
                        className={`w-4 h-4 transition-transform duration-300 ${
                            isExpanded ? "rotate-180" : ""
                        }`}
                    />
                </button>
            )}
        </Section>
    );
});

export default Payments;