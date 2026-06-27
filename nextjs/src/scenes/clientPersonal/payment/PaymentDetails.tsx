'use client'

import { useEffect, useRef } from "react";
import Link from "next/link";
import { usePathname, useSearchParams } from "next/navigation";
import { observer } from "mobx-react-lite";
import { PaymentScope } from "@/api/client/payments.api";
import { useStore } from "@/store/StoreProvider";
import EmptyState from "@/shared/ui/EmptyState";
import ErrorState from "@/shared/ui/ErrorState";
import LoadingState from "@/shared/ui/LoadingState";
import PayPendingPaymentButton from "@/scenes/clientPersonal/payment/PayPendingPaymentButton";
import { PaymentMethodEnum } from "@/types/payment/payment-method.enum";
import { PaymentStatusEnum } from "@/types/payment/payment-status.enum";
import { Roles } from "@/types/auth.type";
import {
    formatPaymentDateTime,
    formatPaymentMoney,
    getPaymentCategoryLabel,
    getPaymentMethodLabel,
    getPaymentStatusClassName,
    getPaymentStatusLabel,
} from "@/scenes/clientPersonal/payment/payment-display";

const STRIPE_RETURN_PARAMS = [
    "payment_intent",
    "payment_intent_client_secret",
    "redirect_status",
] as const;

type PaymentDetailsProps = {
    paymentId: number;
};

const DetailRow = ({ label, value }: { label: string; value: string }) => (
    <div className="flex flex-col gap-1 border-b border-gray-100 py-3 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
        <dt className="text-sm text-gray-500">{label}</dt>
        <dd className="break-all font-semibold sm:text-right">{value}</dd>
    </div>
);

const PaymentDetails = observer(({ paymentId }: PaymentDetailsProps) => {
    const { authStore, paymentStore } = useStore();
    const pathname = usePathname();
    const searchParams = useSearchParams();
    const searchParamsString = searchParams.toString();
    const handledStripeReturn = useRef<string | null>(null);
    const scope = authStore.user?.roles.includes(Roles.TRAINER)
        ? PaymentScope.TRAINER
        : PaymentScope.CLIENT;
    const isTrainerScope = scope === PaymentScope.TRAINER;
    const payment = paymentStore.selectedPayment?.id === paymentId
        && paymentStore.selectedPaymentScope === scope
        ? paymentStore.selectedPayment
        : null;

    useEffect(() => {
        void paymentStore.loadPayment(paymentId, scope);
    }, [paymentId, paymentStore, scope]);

    useEffect(() => {
        if (isTrainerScope) {
            return;
        }

        const params = new URLSearchParams(searchParamsString);
        const hasStripeReturn = STRIPE_RETURN_PARAMS.some((key) => params.has(key));

        if (!hasStripeReturn || handledStripeReturn.current === searchParamsString) {
            return;
        }

        handledStripeReturn.current = searchParamsString;
        void paymentStore.refreshAfterStripeReturn(paymentId).finally(() => {
            STRIPE_RETURN_PARAMS.forEach((key) => params.delete(key));
            const queryString = params.toString();
            window.history.replaceState(
                null,
                "",
                `${pathname}${queryString ? `?${queryString}` : ""}`,
            );
        });
    }, [isTrainerScope, pathname, paymentId, paymentStore, searchParamsString]);

    if (payment === null && paymentStore.isDetailLoading) {
        return (
            <LoadingState
                title="Loading payment..."
                description="We are fetching the latest payment details."
            />
        );
    }

    if (payment === null && paymentStore.detailErrorStatus === 404) {
        return (
            <EmptyState
                title="Payment not found"
                description="This payment does not exist or is no longer available."
                action={(
                    <Link href="/me/payments" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold">
                        Back to payments
                    </Link>
                )}
            />
        );
    }

    if (payment === null && paymentStore.detailErrorStatus === 403) {
        return (
            <EmptyState
                title="Access denied"
                description={isTrainerScope
                    ? "You cannot view a payment that belongs to another trainer."
                    : "You cannot view a payment that belongs to another client."}
                action={(
                    <Link href="/me/payments" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold">
                        Back to payments
                    </Link>
                )}
            />
        );
    }

    if (payment === null && paymentStore.detailError) {
        return (
            <ErrorState
                title="Unable to load payment"
                message={paymentStore.detailError}
                isRetrying={paymentStore.isDetailLoading}
                onRetry={() => void paymentStore.loadPayment(paymentId, scope)}
            />
        );
    }

    if (payment === null) {
        return <LoadingState title="Loading payment..." />;
    }

    const canPayByCard = !isTrainerScope
        && payment.method === PaymentMethodEnum.CARD
        && payment.status === PaymentStatusEnum.PENDING
        && !payment.isRefund;

    return (
        <section className="mx-auto w-full max-w-5xl" aria-busy={paymentStore.isDetailLoading}>
            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <Link
                    href="/me/payments"
                    className="rounded-md border border-gray-300 bg-white px-4 py-2 font-semibold transition hover:border-secondary-500"
                >
                    Back to payments
                </Link>
                {paymentStore.isDetailLoading ? (
                    <p role="status" aria-live="polite" className="text-sm font-semibold text-secondary-500">
                        Refreshing payment...
                    </p>
                ) : null}
            </div>

            {paymentStore.detailError ? (
                <div
                    role="alert"
                    className="mb-6 flex flex-col gap-3 rounded-xl bg-red-50 p-4 text-red-700 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p>{paymentStore.detailError}</p>
                    <button
                        type="button"
                        className="self-start rounded-md border border-red-300 bg-white px-4 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-50 sm:self-auto"
                        disabled={paymentStore.isDetailLoading}
                        onClick={() => void paymentStore.loadPayment(paymentId, scope)}
                    >
                        {paymentStore.isDetailLoading ? "Retrying..." : "Retry"}
                    </button>
                </div>
            ) : null}

            {isTrainerScope ? (
                <div
                    role="note"
                    className="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
                >
                    Client identity is not included in the trainer PaymentResponseDTO, so this read-only view cannot display it without a backend contract change.
                </div>
            ) : null}

            <article className="rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                <div className="flex flex-wrap items-start justify-between gap-5">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                            Payment #{payment.id}
                        </p>
                        <h1 className="mt-2 text-3xl font-bold">
                            {getPaymentCategoryLabel(payment.category)}
                        </h1>
                        <p className="mt-2 text-2xl font-bold">
                            {formatPaymentMoney(payment.amount, payment.currency)}
                        </p>
                    </div>
                    <span className={`rounded-full px-4 py-2 text-sm font-semibold ${getPaymentStatusClassName(payment.status)}`}>
                        {getPaymentStatusLabel(payment.status)}
                    </span>
                </div>

                {canPayByCard ? <PayPendingPaymentButton paymentId={payment.id} /> : null}

                <div className="mt-8 grid gap-6 lg:grid-cols-2">
                    <section className="rounded-xl border border-gray-100 p-5">
                        <h2 className="text-xl font-bold">Payment</h2>
                        <dl className="mt-3">
                            <DetailRow label="Amount" value={formatPaymentMoney(payment.amount, payment.currency)} />
                            <DetailRow label="Currency" value={payment.currency.toUpperCase()} />
                            <DetailRow label="Method" value={getPaymentMethodLabel(payment.method)} />
                            <DetailRow label="Category" value={getPaymentCategoryLabel(payment.category)} />
                            <DetailRow label="Status" value={getPaymentStatusLabel(payment.status)} />
                            <DetailRow label="Refund payment" value={payment.isRefund ? "Yes" : "No"} />
                        </dl>
                    </section>

                    <section className="rounded-xl border border-gray-100 p-5">
                        <h2 className="text-xl font-bold">Dates</h2>
                        <dl className="mt-3">
                            <DetailRow label="Created at" value={formatPaymentDateTime(payment.createdAt)} />
                            <DetailRow label="Paid at" value={formatPaymentDateTime(payment.paidAt, "Not paid")} />
                            <DetailRow label="Expires at" value={formatPaymentDateTime(payment.expiresAt, "No expiry")} />
                        </dl>
                    </section>

                    {!isTrainerScope ? (
                        <section className="rounded-xl border border-gray-100 p-5">
                            <h2 className="text-xl font-bold">Stripe</h2>
                            <dl className="mt-3">
                                <DetailRow
                                    label="Payment intent"
                                    value={payment.stripePaymentIntentId ?? "Not created"}
                                />
                            </dl>
                            {payment.stripePaymentIntentId ? (
                                <p className="mt-4 text-sm text-gray-500">
                                    The existing backend payment record remains the source of truth. Continuing payment reuses Stripe idempotency for this payment ID.
                                </p>
                            ) : null}
                        </section>
                    ) : null}

                    <section className="rounded-xl border border-gray-100 p-5">
                        <h2 className="text-xl font-bold">Related data</h2>
                        <dl className="mt-3">
                            {isTrainerScope ? (
                                <DetailRow label="Client" value="Not exposed by trainer payment API" />
                            ) : (
                                <DetailRow
                                    label="Trainer"
                                    value={payment.trainer
                                        ? `${payment.trainer.firstName} ${payment.trainer.lastName} (#${payment.trainer.id})`
                                        : "Not linked"}
                                />
                            )}
                            <DetailRow
                                label="Original payment"
                                value={payment.originalPayment
                                    ? `#${payment.originalPayment.id} — ${getPaymentStatusLabel(payment.originalPayment.status)}`
                                    : "Not linked"}
                            />
                        </dl>
                        {payment.originalPayment ? (
                            <Link
                                href={`/me/payments/${payment.originalPayment.id}`}
                                className="mt-4 inline-flex rounded-md border border-gray-300 px-4 py-2 font-semibold transition hover:border-secondary-500"
                            >
                                Open original payment
                            </Link>
                        ) : null}
                    </section>
                </div>
            </article>
        </section>
    );
});

export default PaymentDetails;
