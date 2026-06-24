import { ApiClientError, ApiErrorPayload } from "@/lib/apiClient";

const parseErrorPayload = async (response: Response): Promise<ApiErrorPayload> => {
    try {
        return await response.json() as ApiErrorPayload;
    } catch {
        return {};
    }
};

export const publicApiGet = async <T>(path: string): Promise<T> => {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}${path}`, {
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
