import { MembershipStatusEnum } from "@/types/membership/membership-status.enum";

const statusLabels: Record<MembershipStatusEnum, string> = {
    [MembershipStatusEnum.ACTIVE]: "Active",
    [MembershipStatusEnum.EXPIRED]: "Expired",
    [MembershipStatusEnum.FROZEN]: "Frozen",
    [MembershipStatusEnum.PENDING]: "Pending",
    [MembershipStatusEnum.CANCELED_PAYMENT_FAILED]: "Canceled: payment failed",
    [MembershipStatusEnum.CANCELED_BY_CLIENT]: "Canceled by client",
    [MembershipStatusEnum.CANCELED_BY_SYSTEM]: "Canceled by system",
};

const statusClassNames: Record<MembershipStatusEnum, string> = {
    [MembershipStatusEnum.ACTIVE]: "bg-green-100 text-green-800",
    [MembershipStatusEnum.EXPIRED]: "bg-red-100 text-red-800",
    [MembershipStatusEnum.FROZEN]: "bg-blue-100 text-blue-800",
    [MembershipStatusEnum.PENDING]: "bg-yellow-100 text-yellow-800",
    [MembershipStatusEnum.CANCELED_PAYMENT_FAILED]: "bg-red-200 text-red-900",
    [MembershipStatusEnum.CANCELED_BY_CLIENT]: "bg-gray-100 text-gray-800",
    [MembershipStatusEnum.CANCELED_BY_SYSTEM]: "bg-gray-100 text-gray-800",
};

export const getMembershipStatusLabel = (status: MembershipStatusEnum): string => {
    return statusLabels[status];
};

export const getMembershipStatusClassName = (status: MembershipStatusEnum): string => {
    return statusClassNames[status];
};

export const formatMembershipDate = (
    value: string | null,
    fallback = "Not set",
): string => {
    return value ?? fallback;
};

export const formatMembershipDateTime = (value: string | null): string => {
    if (value === null) {
        return "Not set";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("en", {
        dateStyle: "medium",
        timeStyle: "short",
    }).format(date);
};

export const formatMembershipMoney = (amount: number, currency: string): string => {
    return `${(amount / 100).toFixed(2)} ${currency.toUpperCase()}`;
};

export const formatSessionLimit = (sessionLimit: number | null): string => {
    return sessionLimit === null ? "Unlimited" : sessionLimit.toString();
};

export const formatEnumLabel = (value: string): string => {
    return value.replace(/_/g, " ");
};
