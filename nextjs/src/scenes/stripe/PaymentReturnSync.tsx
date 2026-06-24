'use client'

import {useEffect} from "react";
import {usePathname, useRouter, useSearchParams} from "next/navigation";
import {notify} from "@/lib/notify";
import {useStore} from "@/store/StoreProvider";

const STRIPE_RETURN_PARAMS = [
    "payment_intent",
    "payment_intent_client_secret",
    "redirect_status",
] as const;

export const PaymentReturnSync = () => {
    const pathname = usePathname();
    const router = useRouter();
    const searchParams = useSearchParams();
    const {clientStore, bookingStore, membershipStore, paymentStore} = useStore();

    useEffect(() => {
        const clientSecret = searchParams.get("payment_intent_client_secret");

        if (!clientSecret) {
            return;
        }

        const syncPaymentReturn = async () => {
            const redirectStatus = searchParams.get("redirect_status");

            await Promise.all([
                clientStore.init(),
                bookingStore.init(),
                membershipStore.init(),
                paymentStore.init(),
            ]);

            if (redirectStatus === "succeeded") {
                notify.success(
                    "Payment confirmed",
                    "Your account data has been refreshed.",
                );
            } else if (redirectStatus === "processing") {
                notify.info(
                    "Payment processing",
                    "The payment is still being processed by Stripe.",
                );
            } else if (redirectStatus) {
                notify.error(
                    "Payment incomplete",
                    "Stripe did not mark the payment as successful.",
                );
            }

            const nextSearchParams = new URLSearchParams(searchParams.toString());
            STRIPE_RETURN_PARAMS.forEach((param) => nextSearchParams.delete(param));
            const query = nextSearchParams.toString();

            router.replace(query ? `${pathname}?${query}` : pathname, {scroll: false});
        };

        void syncPaymentReturn();
    }, [
        bookingStore,
        clientStore,
        membershipStore,
        pathname,
        paymentStore,
        router,
        searchParams,
    ]);

    return null;
};
