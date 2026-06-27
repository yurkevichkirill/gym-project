import Link from "next/link";
import { PaymentScope } from "@/api/client/payments.api";
import PaymentType from "@/types/payment/payment.type";
import {
    formatPaymentDateTime,
    formatPaymentMoney,
    getPaymentCategoryLabel,
    getPaymentMethodLabel,
    getPaymentStatusClassName,
    getPaymentStatusLabel,
    isIncomingPayment,
} from "@/scenes/clientPersonal/payment/payment-display";

type PaymentCatalogCardProps = {
    payment: PaymentType;
    scope: PaymentScope;
};

const PaymentCatalogCard = ({
    payment,
    scope,
}: PaymentCatalogCardProps) => {
    const isTrainerScope = scope === PaymentScope.TRAINER;
    const incoming = !isTrainerScope
        && isIncomingPayment(payment.category, payment.isRefund);
    const amountPrefix = isTrainerScope ? "" : incoming ? "+ " : "− ";

    return (
        <article className="flex h-full flex-col rounded-2xl bg-white p-5 shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                        Payment #{payment.id}
                    </p>
                    <h2 className="mt-2 text-xl font-bold">
                        {getPaymentCategoryLabel(payment.category)}
                    </h2>
                </div>
                <span className={`rounded-full px-3 py-1 text-sm font-semibold ${getPaymentStatusClassName(payment.status)}`}>
                    {getPaymentStatusLabel(payment.status)}
                </span>
            </div>

            <dl className="mt-5 grid gap-3 text-sm">
                <div className="flex items-center justify-between gap-4">
                    <dt className="text-gray-500">Amount</dt>
                    <dd className={`font-bold ${incoming ? "text-emerald-700" : ""}`}>
                        {amountPrefix}{formatPaymentMoney(payment.amount, payment.currency)}
                    </dd>
                </div>
                <div className="flex items-center justify-between gap-4">
                    <dt className="text-gray-500">Currency</dt>
                    <dd className="font-semibold">{payment.currency.toUpperCase()}</dd>
                </div>
                <div className="flex items-center justify-between gap-4">
                    <dt className="text-gray-500">Method</dt>
                    <dd className="font-semibold">{getPaymentMethodLabel(payment.method)}</dd>
                </div>
                <div className="flex items-center justify-between gap-4">
                    <dt className="text-gray-500">Refund</dt>
                    <dd className="font-semibold">{payment.isRefund ? "Yes" : "No"}</dd>
                </div>
                <div className="flex items-center justify-between gap-4">
                    <dt className="text-gray-500">Created</dt>
                    <dd className="text-right font-semibold">
                        {formatPaymentDateTime(payment.createdAt)}
                    </dd>
                </div>
                {isTrainerScope ? (
                    <div className="flex items-center justify-between gap-4">
                        <dt className="text-gray-500">Client</dt>
                        <dd className="text-right font-semibold">Not exposed by API</dd>
                    </div>
                ) : payment.trainer ? (
                    <div className="flex items-center justify-between gap-4">
                        <dt className="text-gray-500">Trainer</dt>
                        <dd className="text-right font-semibold">
                            {payment.trainer.firstName} {payment.trainer.lastName}
                        </dd>
                    </div>
                ) : null}
            </dl>

            <Link
                href={`/me/payments/${payment.id}`}
                className="mt-6 rounded-md border border-gray-300 px-4 py-2 text-center font-semibold transition hover:border-secondary-500"
            >
                View details
            </Link>
        </article>
    );
};

export default PaymentCatalogCard;
