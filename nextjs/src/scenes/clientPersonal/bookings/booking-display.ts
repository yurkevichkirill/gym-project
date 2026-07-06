import { statusBadgeClassName } from "@/shared/Section";
import { BookingStatusEnum } from "@/types/booking/bookings-status.enum";

const STATUS_LABELS: Record<BookingStatusEnum, string> = {
    [BookingStatusEnum.COMPLETED]: "Completed",
    [BookingStatusEnum.PENDING]: "Pending",
    [BookingStatusEnum.SCHEDULED]: "Scheduled",
    [BookingStatusEnum.CANCELED_BY_TRAINER]: "Canceled by trainer",
    [BookingStatusEnum.CANCELED_BY_CLIENT]: "Canceled by client",
    [BookingStatusEnum.CANCELED_BY_SYSTEM]: "Canceled by system",
    [BookingStatusEnum.CANCELED_PAYMENT_FAILED]: "Canceled after payment failure",
};

const STATUS_STYLES: Record<BookingStatusEnum, string> = {
    [BookingStatusEnum.COMPLETED]: "bg-green-100 text-green-800",
    [BookingStatusEnum.PENDING]: "bg-yellow-100 text-yellow-800",
    [BookingStatusEnum.SCHEDULED]: "bg-blue-100 text-blue-800",
    [BookingStatusEnum.CANCELED_BY_TRAINER]: "bg-red-100 text-red-800",
    [BookingStatusEnum.CANCELED_BY_CLIENT]: "bg-red-100 text-red-800",
    [BookingStatusEnum.CANCELED_BY_SYSTEM]: "bg-gray-100 text-gray-800",
    [BookingStatusEnum.CANCELED_PAYMENT_FAILED]: "bg-red-200 text-red-900",
};

export const getBookingStatusLabel = (status: BookingStatusEnum): string => {
    return STATUS_LABELS[status];
};

export const getBookingStatusClassName = (status: BookingStatusEnum): string => {
    return `${statusBadgeClassName} ${STATUS_STYLES[status]}`;
};

export const formatMoney = (amount: number, currency: string): string => {
    return `${(amount / 100).toFixed(2)} ${currency.toUpperCase()}`;
};

export const formatDateTime = (value: string | null): string => {
    if (!value) {
        return "Not available";
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? value
        : new Intl.DateTimeFormat("en-US", {
            dateStyle: "medium",
            timeStyle: "short",
        }).format(date);
};
