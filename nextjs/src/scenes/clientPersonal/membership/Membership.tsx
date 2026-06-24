'use client'

import { MembershipPlanType } from "@/types/membership/membership-plan.type";
import { MembershipStatusEnum } from "@/types/membership/membership-status.enum";
import { useStore } from "@/store/StoreProvider";
import { notify } from "@/lib/notify";
import { useState } from "react";
import { observer } from "mobx-react-lite";
import { PaymentMethodEnum } from "@/types/payment/payment-method.enum";
import { createStripeIntent } from "@/api/client/payments.api";
import { StripeModal } from "@/scenes/stripe/stripeModal";
import { getErrorMessage } from "@/lib/getErrorMessage";

const statusColorMap: Record<string, string> = {
    active: "bg-green-100 text-green-800",
    expired: "bg-red-100 text-red-800",
    pending: "bg-yellow-100 text-yellow-800",
    frozen: "bg-blue-100 text-blue-800",
    canceled: "bg-gray-100 text-gray-800",
};

type ActionType = "freeze" | "unfreeze" | "terminate" | "renew";

type ButtonConfig = {
    label: string;
    action: ActionType;
    className: string;
};

const ALLOWED_ACTIONS: Record<string, ButtonConfig[]> = {
    [MembershipStatusEnum.ACTIVE]: [
        { label: "Freeze", action: "freeze", className: "bg-blue-50 text-blue-600 hover:bg-blue-100" },
        { label: "Terminate", action: "terminate", className: "bg-red-50 text-red-600 hover:bg-red-100" },
    ],
    [MembershipStatusEnum.FROZEN]: [
        { label: "Unfreeze", action: "unfreeze", className: "bg-green-50 text-green-600 hover:bg-green-100" },
        { label: "Terminate", action: "terminate", className: "bg-red-50 text-red-600 hover:bg-red-100" },
    ],
    [MembershipStatusEnum.EXPIRED]: [
        { label: "Renew", action: "renew", className: "bg-primary-50 text-primary-600 hover:bg-primary-100" },
    ],
};

type Props = {
    id: number;
    membershipPlan: MembershipPlanType;
    startDate: string;
    endDate: string;
    status: MembershipStatusEnum;
    visits: number;
    createdAt: string;
};

const PersonalMembership = observer(({
    id,
    membershipPlan,
    startDate,
    endDate,
    status,
    visits,
}: Props) => {
    const { authStore, membershipStore, paymentStore } = useStore();
    const [isLoading, setIsLoading] = useState(false);
    const [stripeClientSecret, setStripeClientSecret] = useState<string | null>(null);

    const normalizedStatus = String(status).toLowerCase();
    const badgeColors = statusColorMap[normalizedStatus] || "bg-gray-100 text-gray-800";
    const currentActions = ALLOWED_ACTIONS[status] || [];

    const refreshAccountData = async () => {
        await Promise.all([
            authStore.checkAuth(),
            membershipStore.init(),
            paymentStore.init(),
        ]);
    };

    const handleAction = async (action: ActionType) => {
        setIsLoading(true);
        const toastId = notify.loading(`Processing ${action}...`);

        try {
            const res = await membershipStore[action]({ id });

            if (action === "renew") {
                const payment = res?.payment;

                if (payment && payment.method === PaymentMethodEnum.CARD) {
                    await paymentStore.init();
                    notify.dismiss(toastId);

                    const clientSecret = await createStripeIntent(payment.id);
                    setStripeClientSecret(clientSecret);

                    return;
                }

                await refreshAccountData();
                notify.success("Success", "Membership successfully renewed via inner balance!", toastId);

                return;
            }

            await refreshAccountData();
            notify.success("Success", "Membership successfully updated!", toastId);
        } catch (error: unknown) {
            notify.error("Action failed", getErrorMessage(error), toastId);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="border border-gray-200 rounded-xl p-4 flex flex-col justify-between gap-4">
            <div>
                <div className="flex justify-between items-start mb-2 gap-2">
                    <p className="font-semibold">{membershipPlan.name}</p>
                    <span className={`text-sm px-3 py-1 rounded-full ${badgeColors}`}>
                        {String(status).replace(/_/g, " ")}
                    </span>
                </div>

                <div className="mt-2 text-sm">
                    <p>{startDate} — {endDate}</p>
                    <p className="mt-1">Visits left: <span className="font-semibold">{visits}</span></p>
                </div>
            </div>

            {currentActions.length > 0 && (
                <div className="flex flex-wrap gap-2 mt-auto border-t pt-3">
                    {currentActions.map((button) => (
                        <button
                            key={button.action}
                            onClick={() => handleAction(button.action)}
                            disabled={isLoading}
                            className={`text-xs px-3 py-1.5 rounded disabled:opacity-50 cursor-pointer transition-colors ${button.className}`}
                        >
                            {button.label}
                        </button>
                    ))}
                </div>
            )}

            {stripeClientSecret && (
                <StripeModal
                    clientSecret={stripeClientSecret}
                    onClose={() => setStripeClientSecret(null)}
                    onSuccess={() => {
                        setStripeClientSecret(null);
                        void refreshAccountData();
                    }}
                    successTitle="Membership Renewed!"
                    successDescription="The payment succeeded. Your membership data is being refreshed."
                />
            )}
        </div>
    );
});

export default PersonalMembership;
