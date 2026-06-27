'use client'

import Link from "next/link";
import { useState } from "react";
import { observer } from "mobx-react-lite";
import { MembershipPlanType } from "@/types/membership/membership-plan.type";
import { MembershipStatusEnum } from "@/types/membership/membership-status.enum";
import { useStore } from "@/store/StoreProvider";
import { notify } from "@/lib/notify";
import { PaymentMethodEnum } from "@/types/payment/payment-method.enum";
import { createStripeIntent } from "@/api/client/payments.api";
import { StripeModal } from "@/scenes/stripe/stripeModal";
import { getErrorMessage } from "@/lib/getErrorMessage";
import {
    formatMembershipDate,
    formatSessionLimit,
    getMembershipStatusClassName,
    getMembershipStatusLabel,
} from "@/scenes/clientPersonal/membership/membership-display";

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
    name: string;
    sessionLimit: number | null;
    membershipPlan: MembershipPlanType | null;
    startDate: string | null;
    endDate: string | null;
    status: MembershipStatusEnum;
    visits: number;
};

const PersonalMembership = observer(({
    id,
    name,
    sessionLimit,
    membershipPlan,
    startDate,
    endDate,
    status,
    visits,
}: Props) => {
    const { clientStore, membershipStore, paymentStore } = useStore();
    const [isLoading, setIsLoading] = useState(false);
    const [stripeClientSecret, setStripeClientSecret] = useState<string | null>(null);
    const currentActions = ALLOWED_ACTIONS[status] || [];

    const refreshAccountData = async () => {
        await Promise.all([
            clientStore.init(),
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
        <article className="flex flex-col gap-4 rounded-xl border border-gray-200 p-4">
            <div>
                <div className="mb-2 flex items-start justify-between gap-2">
                    <div>
                        <p className="font-semibold">{name}</p>
                        <p className="mt-1 text-xs text-gray-500">
                            {membershipPlan === null ? "Linked plan unavailable" : `Plan #${membershipPlan.id}`}
                        </p>
                    </div>
                    <span className={`rounded-full px-3 py-1 text-sm ${getMembershipStatusClassName(status)}`}>
                        {getMembershipStatusLabel(status)}
                    </span>
                </div>

                <div className="mt-3 text-sm text-gray-700">
                    <p>
                        {formatMembershipDate(startDate, "Not started")} — {formatMembershipDate(endDate)}
                    </p>
                    <p className="mt-1">
                        Visits used: <span className="font-semibold">{visits}</span>
                    </p>
                    <p className="mt-1">
                        Session limit: <span className="font-semibold">{formatSessionLimit(sessionLimit)}</span>
                    </p>
                </div>
            </div>

            <div className="mt-auto flex flex-wrap gap-2 border-t pt-3">
                <Link
                    href={`/me/memberships/${id}`}
                    className="rounded border border-gray-300 px-3 py-1.5 text-xs font-semibold transition hover:border-secondary-500"
                >
                    View details
                </Link>
                {currentActions.map((button) => (
                    <button
                        type="button"
                        key={button.action}
                        onClick={() => handleAction(button.action)}
                        disabled={isLoading}
                        className={`cursor-pointer rounded px-3 py-1.5 text-xs transition-colors disabled:cursor-not-allowed disabled:opacity-50 ${button.className}`}
                    >
                        {button.label}
                    </button>
                ))}
            </div>

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
        </article>
    );
});

export default PersonalMembership;
