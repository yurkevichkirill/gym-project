import {ApiError} from "@/types/auth.type";

const refreshToken = async () => {
    const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/refresh/`, {
        method: 'POST',
        credentials: "include",
    });

    return res.ok;
};

const request = async <T>(
    url: string,
    init?: RequestInit
) => {
    const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}${url}`, {
        ...init,
        credentials: "include",
        headers: {
            'Content-Type': 'application/json',
            ...(init?.headers ?? {}),
        },
    });

    if (res.status === 401) {
        const refreshed = await refreshToken();

        if (refreshed) {
            const retry = await fetch(`${process.env.NEXT_PUBLIC_API_URL}${url}`, {
                ...init,
                credentials: 'include',
            });

            if (!retry.ok) {
                throw new Error("Request failed after refresh");
            }

            return retry.json() as Promise<T>;
        }

        throw new Error("Unauthorized");
    }

    if (!res.ok) {
        const error: ApiError = await res.json();
        throw new Error(error.error);
    }

    return res.json() as Promise<T>;
};

export const apiGet = <T>(url: string) => {
    return request<T>(url, { method: 'GET' });
};

export const apiPost = <T, B = unknown>(
    url: string,
    body: B
): Promise<T> => {
    return request<T>(url, {
        method: 'POST',
        body: JSON.stringify(body),
    });
};

