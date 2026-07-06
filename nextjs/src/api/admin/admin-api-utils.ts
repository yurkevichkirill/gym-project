import { ApiClientError } from "@/lib/apiClient";
import { getErrorMessage } from "@/lib/getErrorMessage";
import type { SearchParamsReader } from "@/types/admin/admin-common.type";

const SORT_ORDERS = new Set(["ASC", "DESC"]);

export const readPositiveInteger = (value: string | null, maximum?: number): number | undefined => {
    if (value === null || !/^\d+$/.test(value)) {
        return undefined;
    }

    const parsed = Number(value);

    if (!Number.isSafeInteger(parsed) || parsed <= 0 || (maximum !== undefined && parsed > maximum)) {
        return undefined;
    }

    return parsed;
};

export const readNonNegativeInteger = (value: string | null, maximum?: number): number | undefined => {
    if (value === null || !/^\d+$/.test(value)) {
        return undefined;
    }

    const parsed = Number(value);

    if (!Number.isSafeInteger(parsed) || parsed < 0 || (maximum !== undefined && parsed > maximum)) {
        return undefined;
    }

    return parsed;
};

export const readBoolean = (value: string | null): boolean | undefined => {
    if (value === "true") {
        return true;
    }

    if (value === "false") {
        return false;
    }

    return undefined;
};

export const readDate = (value: string | null): string | undefined => {
    if (value === null || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return undefined;
    }

    return value;
};

export const readDateTimeLocal = (value: string | null): string | undefined => {
    if (value === null || value.trim().length === 0) {
        return undefined;
    }

    return value;
};

export const readTime = (value: string | null): string | undefined => {
    if (value === null || !/^\d{2}:\d{2}(:\d{2})?$/.test(value)) {
        return undefined;
    }

    return value;
};

export const readEnum = <T extends string>(value: string | null, values: readonly T[]): T | undefined => {
    return value !== null && values.includes(value as T) ? value as T : undefined;
};

export const isValidSort = (value: string, fields: readonly string[]): boolean => {
    const allowedFields = new Set(fields);

    return value.split(",").every((item) => {
        const parts = item.trim().split(":");

        if (parts.length > 2) {
            return false;
        }

        const field = parts[0]?.trim();
        const order = (parts[1] ?? "ASC").trim().toUpperCase();

        return field !== undefined && field.length > 0 && allowedFields.has(field) && SORT_ORDERS.has(order);
    });
};

export const readSort = (
    searchParams: SearchParamsReader,
    fields: readonly string[],
): string | undefined => {
    const sort = searchParams.get("sort");

    return sort !== null && isValidSort(sort, fields) ? sort : undefined;
};

export const createAdminSearchParams = <T extends object>(params: T): URLSearchParams => {
    const searchParams = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== "") {
            searchParams.set(key, value.toString());
        }
    });

    return searchParams;
};

export const getAdminRequestKey = <T extends object>(params: T = {} as T): string => {
    return createAdminSearchParams(params).toString();
};

export const getApiErrorStatus = (error: unknown): number | null => (
    error instanceof ApiClientError ? error.status : null
);

export const getAdminErrorMessage = (error: unknown, fallback: string): string => {
    if (error instanceof ApiClientError) {
        if (error.status === 403) {
            return "Access denied for this administrative action.";
        }

        if (error.status === 404) {
            return "The requested record was not found.";
        }

        if (error.status === 409) {
            return error.payload.message || "The requested action conflicts with the current backend state.";
        }

        if (error.status === 422 || error.status === 400) {
            return error.payload.message || "Please check the submitted values and try again.";
        }

        if (error.status === 429) {
            return "Too many requests. Please try again later.";
        }
    }

    return getErrorMessage(error, fallback);
};

