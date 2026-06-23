export interface ApiViolation {
    propertyPath?: string;
    title?: string;
    message?: string;
}

export interface ApiErrorPayload {
    message?: string;
    violations?: ApiViolation[];
}

export class ApiClientError extends Error {
    public constructor(
        public readonly status: number,
        public readonly payload: ApiErrorPayload,
    ) {
        super(payload.message || "Request failed");
        this.name = "ApiClientError";
    }
}

const refreshToken = async () => {
    const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/refresh/`, {
        method: 'POST',
        credentials: "include",
    });

    return res.ok;
};

const parseErrorPayload = async (response: Response): Promise<ApiErrorPayload> => {
    try {
        return await response.json() as ApiErrorPayload;
    } catch {
        return {};
    }
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

        throw new ApiClientError(res.status, {message: "Unauthorized"});
    }

    if (!res.ok) {
        const error = await parseErrorPayload(res);
        throw new ApiClientError(res.status, error);
    }

    if (res.status === 204) {
        return null as T;
    }

    return await res.json() as Promise<T>;
};

export const apiGet = <T>(url: string) => {
    return request<T>(url, { method: 'GET' });
};

export const apiPost = <T, B = unknown>(
    url: string,
    body?: B
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
