import { statusBadgeClassName } from "@/shared/Section";

export const formatMoney = (amount: number, currency = "USD"): string => {
    try {
        return new Intl.NumberFormat("en-US", { style: "currency", currency }).format(amount / 100);
    } catch {
        return `${amount} ${currency}`;
    }
};

export const formatDateTime = (value: string | null | undefined): string => {
    if (!value) {
        return "Not set";
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat("en-US", { dateStyle: "medium", timeStyle: "short" }).format(parsed);
};

export const formatDate = (value: string | null | undefined): string => value || "Not set";
export const formatNullable = (value: string | number | null | undefined): string => (
    value === null || value === undefined || value === "" ? "Not set" : value.toString()
);

export const humanize = (value: string | null | undefined): string => {
    if (!value) {
        return "Not set";
    }

    return value.replace(/_/g, " ").replace(/([a-z])([A-Z])/g, "$1 $2");
};

export const statusClassName = (status?: string | null): string => {
    const positive = new Set(["active", "scheduled", "succeeded", "completed"]);
    const warning = new Set(["pending", "frozen", "refund_pending"]);
    const normalized = status ?? "";

    if (positive.has(normalized)) {
        return `${statusBadgeClassName} bg-emerald-50 text-emerald-700`;
    }

    if (warning.has(normalized)) {
        return `${statusBadgeClassName} bg-secondary-400/25 text-gray-500`;
    }

    return `${statusBadgeClassName} bg-primary-100 text-gray-500`;
};
