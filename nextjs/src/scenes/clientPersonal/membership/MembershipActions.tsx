'use client'

import { useState } from "react";
import { observer } from "mobx-react-lite";
import { createStripeIntent } from "@/api/client/payments.api";
import { notify } from "@/lib/notify";
import ConfirmDialog from "@/shared/ui/ConfirmDialog";
import { StripeModal } from "@/scenes/stripe/stripeModal";
import { useStore } from "@/store/StoreProvider";
import MembershipType from "@/types/membership/membership.type";
import { MembershipStatusEnum } from "@/types/membership/membership-status.enum";
import { PaymentMethodEnum } from "@/types/payment/payment-method.enum";
import {
    getMembershipActions,
    type MembershipAction,
} from "@/scenes/clientPersonal/membership/membership-actions";
import { getMembershipMutationErrorMessage } from "@/scenes/clientPersonal/membership/membership-mutation-error";

type ActionConfig = {
    label: string;
    loadingMessage: string;
    successTitle: string;
    successDescription: string;
    className: string;
    confirmation?: {
        title: string;
        description: string;
        confirmLabel: string;
    };
};

const ACTION_CONFIG: Record<MembershipAction, ActionConfig> = {
    cancel: {
        label: "Cancel",
        loadingMessage: "Cancelling membership...",
        successTitle: "Membership cancelled",
        successDescription: "The pending membership and its payment were cancelled.",
        className: "border-red-200 bg-red-50 text-red-700 hover:bg-red-100",
        confirmation: {
            title: "Cancel pending membership?",
            description: "This cancels the pending membership and its associated pending payment. This action cannot be undone.",
            confirmLabel: "Cancel membership",
        },
    },
    freeze: {
        label: "Freeze",
        loadingMessage: "Freezing membership...",
        successTitle: "Membership frozen",
        successDescription: "The server marked the membership as frozen.",
        className: "border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100",
    },
    unfreeze: {
        label: "Unfreeze",
        loadingMessage: "Unfreezing membership...",
        successTitle: "Membership unfrozen",
        successDescription: "The server restored the membership and extended its end date.",
        className: "border-green-200 bg-green-50 text-green-700 hover:bg-green-100",
    },
    renew: {
        label: "Renew",
        loadingMessage: "Creating membership renewal...",
        successTitle: "Membership renewed",
        successDescription: "The renewed membership was activated using your balance.",
        className: "border-primary-200 bg-primary-50 text-primary-700 hover:bg-primary-100",
    },
    terminate: {
        label: "Terminate",
        loadingMessage: "Terminating membership...",
        successTitle: "Membership terminated",
        successDescription: "The server marked the membership as expired.",
        className: "border-red-200 bg-red-50 text-red-700 hover:bg-red-100",
        confirmation: {
            title: "Terminate membership?",
            description: "This immediately expires the membership and sets its end date on the server. This action cannot be undone.",
            confirmLabel: "Terminate membership",
        },
    },
};

type MembershipActionsProps = {
    membershipId: number;
    status: MembershipStatusEnum;
    compact?: boolean;
    className?: string;
};

const MembershipActions = observer(({
    membershipId,
    status,
    compact = false,
    className = "",
}: MembershipActionsProps) => {
    const { membershipStore } = useStore();
    const [pendingConfirmation, setPendingConfirmation] = useState<MembershipAction | null>(null);
    const [stripeClientSecret, setStripeClientSecret] = useState<string | null>(null);
    const actions = getMembershipActions(status);
    const isMutating = membershipStore.isMutating(membershipId);
    const buttonSizeClassName = compact ? "min-h-10 px-4 py-2 text-sm" : "min-h-11 px-5 py-2.5 text-sm";

    const callAction = (action: MembershipAction): Promise<MembershipType> => {
        switch (action) {
            case "cancel":
                return membershipStore.cancel({ id: membershipId });
            case "freeze":
                return membershipStore.freeze({ id: membershipId });
            case "unfreeze":
                return membershipStore.unfreeze({ id: membershipId });
            case "renew":
                return membershipStore.renew({ id: membershipId });
            case "terminate":
                return membershipStore.terminate({ id: membershipId });
        }
    };

    const executeAction = async (action: MembershipAction): Promise<void> => {
        const config = ACTION_CONFIG[action];
        const toastId = notify.loading(config.loadingMessage);
        let membership: MembershipType;

        try {
            membership = await callAction(action);
        } catch (error: unknown) {
            notify.error(
                `${config.label} failed`,
                getMembershipMutationErrorMessage(
                    error,
                    `Unable to ${config.label.toLowerCase()} this membership.`,
                ),
                toastId,
            );
            return;
        }

        if (action === "renew" && membership.payment.method === PaymentMethodEnum.CARD) {
            notify.dismiss(toastId);

            try {
                const clientSecret = await createStripeIntent(membership.payment.id);
                setStripeClientSecret(clientSecret);
            } catch (error: unknown) {
                notify.error(
                    "Renewal created, payment form unavailable",
                    `Membership #${membership.id} already exists. Do not renew again. ${getMembershipMutationErrorMessage(
                        error,
                        "Open your payments later and retry the card payment.",
                    )}`,
                );
            }

            return;
        }

        notify.success(
            config.successTitle,
            config.successDescription,
            toastId,
        );
    };

    const startAction = (action: MembershipAction) => {
        if (ACTION_CONFIG[action].confirmation) {
            setPendingConfirmation(action);
            return;
        }

        void executeAction(action);
    };

    const confirmAction = async () => {
        if (pendingConfirmation === null) {
            return;
        }

        await executeAction(pendingConfirmation);
        setPendingConfirmation(null);
    };

    const closeStripe = () => {
        setStripeClientSecret(null);
        void membershipStore.refreshAfterPayment(membershipId);
    };

    const confirmation = pendingConfirmation === null
        ? null
        : ACTION_CONFIG[pendingConfirmation].confirmation;

    return (
        <>
            {actions.length > 0 ? (
                <div className={`flex flex-wrap gap-2 ${className}`}>
                    {actions.map((action) => {
                        const config = ACTION_CONFIG[action];

                        return (
                            <button
                                type="button"
                                key={action}
                                onClick={() => startAction(action)}
                                disabled={isMutating}
                                className={`inline-flex items-center justify-center rounded-xl border font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 disabled:cursor-not-allowed disabled:opacity-50 ${buttonSizeClassName} ${config.className}`}
                            >
                                {isMutating ? "Processing..." : config.label}
                            </button>
                        );
                    })}
                </div>
            ) : null}

            <ConfirmDialog
                open={pendingConfirmation !== null}
                title={confirmation?.title ?? "Confirm membership action"}
                description={confirmation?.description ?? "Confirm this membership action."}
                confirmLabel={confirmation?.confirmLabel ?? "Confirm"}
                tone="danger"
                isConfirming={isMutating}
                onConfirm={() => void confirmAction()}
                onCancel={() => setPendingConfirmation(null)}
            />

            {stripeClientSecret !== null ? (
                <StripeModal
                    clientSecret={stripeClientSecret}
                    onClose={closeStripe}
                    onSuccess={closeStripe}
                    successTitle="Membership Renewed!"
                    successDescription="The payment succeeded. Membership, profile, and payment data are being refreshed."
                />
            ) : null}
        </>
    );
});

export default MembershipActions;
