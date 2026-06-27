'use client'

import Link from "next/link";
import Payment from "@/scenes/clientPersonal/payment/Payment";
import Section from "@/shared/Section";
import { useStore } from "@/store/StoreProvider";
import { observer } from "mobx-react-lite";
import { useEffect } from "react";

export const Payments = observer(() => {
    const { paymentStore } = useStore();
    const hasPayments = paymentStore.payments.length > 0;

    useEffect(() => {
        void paymentStore.init();
    }, [paymentStore]);

    return (
        <Section title="Payments">
            <div className="flex flex-col gap-3">
                {paymentStore.isLoading && !hasPayments ? (
                    <p className="text-sm text-gray-500">Loading payments...</p>
                ) : null}

                {paymentStore.error ? (
                    <div className="rounded-md border border-primary-500 bg-red-50 p-4" role="alert">
                        <p className="font-semibold">Unable to load payments.</p>
                        <p className="mt-1 text-sm text-gray-600">{paymentStore.error}</p>
                        <button
                            type="button"
                            onClick={() => void paymentStore.init()}
                            disabled={paymentStore.isLoading || paymentStore.isRefreshing}
                            className="mt-3 rounded-md bg-secondary-500 px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {paymentStore.isLoading || paymentStore.isRefreshing ? "Retrying..." : "Retry"}
                        </button>
                    </div>
                ) : null}

                {!paymentStore.isLoading && !paymentStore.error && !hasPayments ? (
                    <p className="text-sm text-gray-500">You have no payments yet.</p>
                ) : null}

                {paymentStore.payments.slice(0, 3).map((payment) => (
                    <Payment key={payment.id} payment={payment} />
                ))}

                {paymentStore.isRefreshing && hasPayments ? (
                    <p className="text-sm text-gray-500">Refreshing payments...</p>
                ) : null}
            </div>

            <Link
                href="/me/payments"
                className="mt-5 inline-flex rounded-md border border-gray-300 px-4 py-2 font-semibold transition hover:border-secondary-500"
            >
                View all payments
            </Link>
        </Section>
    );
});

export default Payments;
