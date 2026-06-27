'use client'

import { useEffect, useState } from "react";
import { observer } from "mobx-react-lite";
import { createStripeIntent } from "@/api/client/payments.api";
import { notify } from "@/lib/notify";
import { isClient } from "@/lib/utils/user.types.utils";
import { StripeModal } from "@/scenes/stripe/stripeModal";
import { getMembershipMutationErrorMessage } from "@/scenes/clientPersonal/membership/membership-mutation-error";
import { useStore } from "@/store/StoreProvider";
import { PaymentMethodEnum } from "@/types/payment/payment-method.enum";

type PurchaseMembershipButtonProps = {
    membershipPlanId: number;
    membershipPlanName: string;
    className?: string;
};

const PurchaseMembershipButton = observer(({
    membershipPlanId,
    membershipPlanName,
    className = "",
}: PurchaseMembershipButtonProps) => {
    const { authStore, membershipStore } = useStore();
    const [stripeClientSecret, setStripeClientSecret] = useState<string | null>(null);
    const [purchasedMembershipId, setPurchasedMembershipId] = useState<number | null>(null);
    const user = authStore.user;
    const isClientAccount = user !== null && isClient(user);
    const isCurrentPurchase = membershipStore.purchasingPlanId === membershipPlanId;
    const isDisabled = !authStore.isInitialized
        || !isClientAccount
        || membershipStore.isPurchasing;

    useEffect(() => {
        if (!authStore.isInitialized) {
            void authStore.checkAuth();
        }
    }, [authStore]);

    const handlePurchase = async () => {
        if (!isClientAccount || membershipStore.isPurchasing) {
            return;
        }

        const toastId = notify.loading("Initiating membership purchase...");

        try {
            const membership = await membershipStore.buy({ membershipPlanId });
            setPurchasedMembershipId(membership.id);

            if (membership.payment.method === PaymentMethodEnum.CARD) {
                notify.dismiss(toastId);

                try {
                    const clientSecret = await createStripeIntent(membership.payment.id);
                    setStripeClientSecret(clientSecret);
                } catch (error: unknown) {
                    notify.error(
                        "Membership created, payment form unavailable",
                        `Membership #${membership.id} already exists. Do not purchase the plan again. ${getMembershipMutationErrorMessage(
                            error,
                            "Open your payments later and retry the card payment.",
                        )}`,
                    );
                }

                return;
            }

            notify.success(
                "Membership activated",
                `Membership "${membership.name}" was activated using your balance.`,
                toastId,
            );
        } catch (error: unknown) {
            notify.error(
                "Purchase failed",
                getMembershipMutationErrorMessage(
                    error,
                    `Unable to purchase membership plan "${membershipPlanName}".`,
                ),
                toastId,
            );
        }
    };

    const closeStripe = () => {
        setStripeClientSecret(null);
        void membershipStore.refreshAfterPayment(purchasedMembershipId ?? undefined);
    };

    const label = !authStore.isInitialized
        ? "Checking account..."
        : !isClientAccount
            ? "Client account required"
            : isCurrentPurchase
                ? "Processing..."
                : membershipStore.isPurchasing
                    ? "Purchase in progress"
                    : "Buy plan";

    return (
        <>
            <button
                type="button"
                disabled={isDisabled}
                onClick={() => void handlePurchase()}
                className={`rounded-md bg-secondary-500 px-4 py-2 text-center font-semibold transition hover:bg-primary-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50 ${className}`}
            >
                {label}
            </button>

            {stripeClientSecret !== null ? (
                <StripeModal
                    clientSecret={stripeClientSecret}
                    onClose={closeStripe}
                    onSuccess={closeStripe}
                    successTitle="Membership Activated!"
                    successDescription="The payment succeeded. Membership, profile, and payment data are being refreshed."
                />
            ) : null}
        </>
    );
});

export default PurchaseMembershipButton;
