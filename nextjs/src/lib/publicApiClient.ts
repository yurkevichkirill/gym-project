import { ApiClientError } from "@/lib/apiClient";
import type { ApiErrorPayload } from "@/lib/apiClient";

const parseErrorPayload = async (response: Response): Promise<ApiErrorPayload> => {
    try {
        return await response.json() as ApiErrorPayload;
    } catch {
        return {};
    }
};

const getApiBaseUrl = (): string => {
    const apiBaseUrl = typeof window === "undefined"
        ? process.env.INTERNAL_API_URL || process.env.NEXT_PUBLIC_API_URL
        : process.env.NEXT_PUBLIC_API_URL;

    if (!apiBaseUrl) {
        throw new Error("API URL is not configured.");
    }

    return apiBaseUrl.replace(/\/$/, "");
};

export const publicApiGet = async <T>(path: string): Promise<T> => {
    const response = await fetch(`${getApiBaseUrl()}${path}`, {
        cache: "no-store",
    });

    if (!response.ok) {
        const payload = await parseErrorPayload(response);

        throw new ApiClientError(response.status, {
            ...payload,
            message: payload.message || `HTTP ${response.status}`,
        });
    }

    return await response.json() as T;
};
