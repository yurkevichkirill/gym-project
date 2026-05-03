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
    init?: RequestInit,
    isRetry = false
) => {
    const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}${url}`, {
        ...init,
        credentials: "include",
        headers: {
            'Content-Type': 'application/json',
            ...(init?.headers ?? {}),
        },
    });

    if (res.status === 401 && !isRetry) {
        const refreshed = await refreshToken();

        if (refreshed) {
            return request<T>(url, init, true);
        }

        throw new Error("Unauthorized");
    }

    if (!res.ok) {
        const error: ApiError = await res.json();
        throw new Error(error.message || "Request failed");
    }

    if (res.status === 204) {
        return null as T;
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

export const apiPatch = <T, B = unknown>(
    url: string,
    body: B
): Promise<T> => {
    return request<T>(url, {
        method: 'PATCH',
        body: JSON.stringify(body),
    });
};


export const apiDelete = <T>(url: string) => {
    return request<T>(url, { method: 'DELETE' });
};
