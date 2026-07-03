'use client'

import { useState } from "react";
import { observer } from "mobx-react-lite";
import { StripeModal } from "@/scenes/stripe/stripeModal";
import { useStore } from "@/store/StoreProvider";
import { getErrorMessage } from "@/lib/getErrorMessage";

type PayPendingPaymentButtonProps = {
    paymentId: number;
};

const PayPendingPaymentButton = observer(({ paymentId }: PayPendingPaymentButtonProps) => {
    const { paymentStore } = useStore();
    const [clientSecret, setClientSecret] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const isCreatingIntent = paymentStore.isCreatingIntent(paymentId);

    const openPayment = async () => {
        setError(null);

        try {
            setClientSecret(await paymentStore.createIntent(paymentId));
        } catch (requestError: unknown) {
            setError(getErrorMessage(
                requestError,
                "Unable to prepare the Stripe payment. Refresh the payment before trying again.",
            ));
            await paymentStore.loadPayment(paymentId);
        }
    };

    const closeAndRefresh = () => {
        setClientSecret(null);
        void paymentStore.refreshAfterStripeReturn(paymentId);
    };

    return (
        <div className="mt-6">
            <button
                type="button"
                className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                disabled={isCreatingIntent}
                onClick={() => void openPayment()}
            >
                {isCreatingIntent ? "Preparing payment..." : "Continue card payment"}
            </button>

            {error ? (
                <p className="mt-3 text-sm text-red-600" role="alert">{error}</p>
            ) : null}

            {clientSecret ? (
                <StripeModal
                    clientSecret={clientSecret}
                    onClose={closeAndRefresh}
                    onSuccess={closeAndRefresh}
                    successTitle="Payment submitted"
                    successDescription="The payment status is being refreshed from the server."
                />
            ) : null}
        </div>
    );
});

export default PayPendingPaymentButton;
