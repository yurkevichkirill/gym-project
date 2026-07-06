import Link from "next/link";
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
import { previewCardClassName } from "@/shared/Section";

type PaymentProps = {
    payment: PaymentType;
};

const Payment = ({ payment }: PaymentProps) => {
    const incoming = isIncomingPayment(payment.category, payment.isRefund);

    return (
        <Link
            href={`/me/payments/${payment.id}`}
            className={`${previewCardClassName} flex flex-col gap-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 sm:flex-row sm:items-center sm:justify-between`}
        >
            <div className="min-w-0">
                <p className="font-semibold text-gray-900">
                    {getPaymentCategoryLabel(payment.category)}
                </p>
                <p className="mt-1 text-sm text-gray-500">
                    {formatPaymentDateTime(payment.paidAt || payment.createdAt)}
                    {" · "}
                    {getPaymentMethodLabel(payment.method)}
                    {payment.isRefund ? " · Refund" : ""}
                </p>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3 border-t border-gray-50 pt-4 sm:flex-col sm:items-end sm:border-t-0 sm:pt-0">
                <p className={`font-bold ${incoming ? "text-emerald-700" : "text-gray-900"}`}>
                    {incoming ? "+" : "−"} {formatPaymentMoney(payment.amount, payment.currency)}
                </p>
                <span className={getPaymentStatusClassName(payment.status)}>
                    {getPaymentStatusLabel(payment.status)}
                </span>
            </div>
        </Link>
    );
};

export default Payment;
