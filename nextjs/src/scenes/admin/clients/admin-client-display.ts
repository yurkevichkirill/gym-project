import { AdminClient } from "@/types/admin/admin-client.type";

export const formatAdminClientDate = (value: string, fallback = "Never"): string => {
    if (value.length === 0) {
        return fallback;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return fallback;
    }

    return new Intl.DateTimeFormat("en", {
        year: "numeric",
        month: "short",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

export const formatAdminClientMoney = (amount: number): string => {
    return new Intl.NumberFormat("en", {
        style: "currency",
        currency: "USD",
        maximumFractionDigits: 0,
    }).format(amount);
};

export const getAdminClientState = (client: AdminClient): "deleted" | "blocked" | "active" => {
    if (client.deletedAt.length > 0) {
        return "deleted";
    }

    if (client.blockedAt.length > 0) {
        return "blocked";
    }

    return "active";
};

export const getAdminClientStateLabel = (client: AdminClient): string => {
    const state = getAdminClientState(client);

    if (state === "deleted") {
        return "Deleted";
    }

    if (state === "blocked") {
        return "Blocked";
    }

    return "Active";
};

export const getAdminClientStateClassName = (client: AdminClient): string => {
    const state = getAdminClientState(client);

    if (state === "deleted") {
        return "bg-gray-100 text-gray-700";
    }

    if (state === "blocked") {
        return "bg-red-100 text-red-700";
    }

    return "bg-emerald-100 text-emerald-700";
};
