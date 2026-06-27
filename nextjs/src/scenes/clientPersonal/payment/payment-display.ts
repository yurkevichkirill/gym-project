import { PaymentCategoryEnum } from "@/types/payment/payment-category.enum";
import { PaymentMethodEnum } from "@/types/payment/payment-method.enum";
import { PaymentStatusEnum } from "@/types/payment/payment-status.enum";

const PAYMENT_STATUS_LABELS: Record<PaymentStatusEnum, string> = {
    [PaymentStatusEnum.SUCCEEDED]: "Succeeded",
    [PaymentStatusEnum.PENDING]: "Pending",
    [PaymentStatusEnum.FAILED]: "Failed",
    [PaymentStatusEnum.CANCELED]: "Canceled",
    [PaymentStatusEnum.REFUND_PENDING]: "Refund pending",
    [PaymentStatusEnum.REFUNDED]: "Refunded",
    [PaymentStatusEnum.REFUND_FAILED]: "Refund failed",
};

const PAYMENT_STATUS_CLASSES: Record<PaymentStatusEnum, string> = {
    [PaymentStatusEnum.SUCCEEDED]: "bg-emerald-100 text-emerald-800",
    [PaymentStatusEnum.PENDING]: "bg-amber-100 text-amber-800",
    [PaymentStatusEnum.FAILED]: "bg-rose-100 text-rose-800",
    [PaymentStatusEnum.CANCELED]: "bg-gray-100 text-gray-800",
    [PaymentStatusEnum.REFUND_PENDING]: "bg-sky-100 text-sky-800",
    [PaymentStatusEnum.REFUNDED]: "bg-blue-100 text-blue-800",
    [PaymentStatusEnum.REFUND_FAILED]: "bg-red-100 text-red-800",
};

const PAYMENT_CATEGORY_LABELS: Record<PaymentCategoryEnum, string> = {
    [PaymentCategoryEnum.MEMBERSHIP]: "Membership",
    [PaymentCategoryEnum.TRAINER]: "Trainer session",
    [PaymentCategoryEnum.BALANCE_TOP_UP]: "Balance top-up",
};

const PAYMENT_METHOD_LABELS: Record<PaymentMethodEnum, string> = {
    [PaymentMethodEnum.BALANCE]: "Balance",
    [PaymentMethodEnum.CARD]: "Card",
};

export const getPaymentStatusLabel = (status: PaymentStatusEnum): string => {
    return PAYMENT_STATUS_LABELS[status];
};

export const getPaymentStatusClassName = (status: PaymentStatusEnum): string => {
    return PAYMENT_STATUS_CLASSES[status];
};

export const getPaymentCategoryLabel = (category: PaymentCategoryEnum): string => {
    return PAYMENT_CATEGORY_LABELS[category];
};

export const getPaymentMethodLabel = (method: PaymentMethodEnum): string => {
    return PAYMENT_METHOD_LABELS[method];
};

export const formatPaymentMoney = (amount: number, currency: string): string => {
    try {
        return new Intl.NumberFormat("en-US", {
            style: "currency",
            currency: currency.toUpperCase(),
        }).format(amount / 100);
    } catch {
        return `${(amount / 100).toFixed(2)} ${currency.toUpperCase()}`;
    }
};

export const formatPaymentDateTime = (
    value: string | null,
    fallback = "Not available",
): string => {
    if (!value) {
        return fallback;
    }

    const parsed = new Date(value);

    return Number.isNaN(parsed.getTime())
        ? value
        : parsed.toLocaleString("en-US", {
            dateStyle: "medium",
            timeStyle: "short",
        });
};

export const isIncomingPayment = (
    category: PaymentCategoryEnum,
    isRefund: boolean,
): boolean => {
    return isRefund || category === PaymentCategoryEnum.BALANCE_TOP_UP;
};
