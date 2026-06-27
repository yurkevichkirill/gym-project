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

type PaymentProps = {
    payment: PaymentType;
};

const Payment = ({ payment }: PaymentProps) => {
    const incoming = isIncomingPayment(payment.category, payment.isRefund);

    return (
        <Link
            href={`/me/payments/${payment.id}`}
            className="flex flex-col gap-4 rounded-2xl border border-gray-100 p-4 transition hover:border-secondary-500 hover:shadow-sm sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
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

            <div className="flex items-center justify-between gap-4 sm:flex-col sm:items-end sm:gap-2">
                <p className={`font-bold ${incoming ? "text-emerald-700" : "text-gray-900"}`}>
                    {incoming ? "+" : "−"} {formatPaymentMoney(payment.amount, payment.currency)}
                </p>
                <span className={`rounded-full px-3 py-1 text-sm font-semibold ${getPaymentStatusClassName(payment.status)}`}>
                    {getPaymentStatusLabel(payment.status)}
                </span>
            </div>
        </Link>
    );
};

export default Payment;
