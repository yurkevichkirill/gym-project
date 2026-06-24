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
    const hasPayments = paymentStore.payments.length > 0;

    useEffect(() => {
        void paymentStore.init();
    }, [paymentStore]);

    const visiblePayments = isExpanded ? paymentStore.payments : paymentStore.payments.slice(0, 3);

    return (
        <Section title="Payments">
            <div className="flex flex-col gap-3">
                {paymentStore.isLoading && !hasPayments && (
                    <p className="text-sm text-gray-500">Loading payments...</p>
                )}

                {paymentStore.error && (
                    <div className="rounded-md border border-primary-500 bg-red-50 p-4" role="alert">
                        <p className="font-semibold">Unable to load payments.</p>
                        <p className="mt-1 text-sm text-gray-600">{paymentStore.error}</p>
                        <button
                            type="button"
                            onClick={() => void paymentStore.init()}
                            disabled={paymentStore.isLoading}
                            className="mt-3 rounded-md bg-secondary-500 px-4 py-2 text-sm cursor-pointer disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {paymentStore.isLoading ? "Retrying..." : "Retry"}
                        </button>
                    </div>
                )}

                {!paymentStore.isLoading && !paymentStore.error && !hasPayments && (
                    <p className="text-sm text-gray-500">You have no payments yet.</p>
                )}

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
                        paidAt={payment.paidAt}
                    />
                ))}

                {paymentStore.isLoading && hasPayments && (
                    <p className="text-sm text-gray-500">Refreshing payments...</p>
                )}
            </div>

            {paymentStore.payments.length > 3 && (
                <button
                    type="button"
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
