'use client'

import Link from "next/link";
import Payment from "@/scenes/clientPersonal/payment/Payment";
import Section, {
    emptyStateClassName,
    errorStateClassName,
    loadingStateClassName,
    primaryActionClassName,
    secondaryActionClassName,
} from "@/shared/Section";
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
        <Section
            title="Payments"
            titleId="payments-title"
            action={(
                <Link
                    href="/me/payments"
                    className={secondaryActionClassName}
                    aria-label="View all payments"
                >
                    View all payments
                </Link>
            )}
        >
            <div className="flex flex-col gap-4">
                {paymentStore.isLoading && !hasPayments ? (
                    <div role="status" aria-live="polite" className={loadingStateClassName}>Loading payments...</div>
                ) : null}

                {paymentStore.error ? (
                    <div className={errorStateClassName} role="alert">
                        <p className="font-semibold">Unable to load payments.</p>
                        <p className="mt-1 text-sm">{paymentStore.error}</p>
                        <button
                            type="button"
                            onClick={() => void paymentStore.init()}
                            disabled={paymentStore.isLoading || paymentStore.isRefreshing}
                            className={`${primaryActionClassName} mt-3`}
                        >
                            {paymentStore.isLoading || paymentStore.isRefreshing ? "Retrying..." : "Retry"}
                        </button>
                    </div>
                ) : null}

                {!paymentStore.isLoading && !paymentStore.error && !hasPayments ? (
                    <div className={emptyStateClassName}>You have no payments yet.</div>
                ) : null}

                {paymentStore.payments.slice(0, 3).map((payment) => (
                    <Payment key={payment.id} payment={payment} />
                ))}

                {paymentStore.isRefreshing && hasPayments ? (
                    <p className="text-sm text-gray-600" role="status" aria-live="polite">Refreshing payments...</p>
                ) : null}
            </div>
        </Section>
    );
});

export default Payments;
