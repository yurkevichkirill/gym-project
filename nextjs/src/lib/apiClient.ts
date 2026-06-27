export interface ApiViolation {
    propertyPath?: string;
    title?: string;
    message?: string;
}

export interface ApiErrorPayload {
    message?: string;
    violations?: ApiViolation[];
}

export interface ApiRequestOptions {
    skipAuthRefresh?: boolean;
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

let refreshPromise: Promise<boolean> | null = null;
let authSessionGeneration = 0;
let logoutInProgress = false;

const refreshToken = (requestGeneration: number): Promise<boolean> => {
    if (logoutInProgress || requestGeneration !== authSessionGeneration) {
        return Promise.resolve(false);
    }

    if (refreshPromise === null) {
        const refreshGeneration = authSessionGeneration;

        refreshPromise = fetch(`${process.env.NEXT_PUBLIC_API_URL}/refresh/`, {
            method: "POST",
            credentials: "include",
        })
            .then((response) => (
                response.ok
                && refreshGeneration === authSessionGeneration
                && !logoutInProgress
            ))
            .catch(() => false)
            .finally(() => {
                refreshPromise = null;
            });
    }

    return refreshPromise.then((refreshed) => (
        refreshed
        && requestGeneration === authSessionGeneration
        && !logoutInProgress
    ));
};

export const beginAuthLogout = async (): Promise<void> => {
    logoutInProgress = true;
    authSessionGeneration += 1;

    const pendingRefresh = refreshPromise;
    if (pendingRefresh !== null) {
        await pendingRefresh.catch(() => false);
    }
};

export const finishAuthLogout = (): void => {
    authSessionGeneration += 1;
    logoutInProgress = false;
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
    options: ApiRequestOptions = {},
    isRetry = false,
    requestGeneration = authSessionGeneration,
): Promise<T> => {
    const isFormDataBody = typeof FormData !== "undefined"
        && init?.body instanceof FormData;

    const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}${url}`, {
        ...init,
        credentials: "include",
        headers: isFormDataBody
            ? init?.headers
            : {
                "Content-Type": "application/json",
                ...(init?.headers ?? {}),
            },
    });

    if (
        res.status === 401
        && !isRetry
        && !options.skipAuthRefresh
        && !logoutInProgress
        && requestGeneration === authSessionGeneration
    ) {
        const refreshed = await refreshToken(requestGeneration);

        if (refreshed) {
            return request<T>(url, init, options, true, requestGeneration);
        }
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

export const apiGet = <T>(
    url: string,
    options?: ApiRequestOptions,
): Promise<T> => {
    return request<T>(url, {method: "GET"}, options);
};

export const apiPost = <T, B = unknown>(
    url: string,
    body?: B,
    options?: ApiRequestOptions,
): Promise<T> => {
    return request<T>(url, {
        method: "POST",
        body: JSON.stringify(body),
    }, options);
};

export const apiPostFormData = <T>(
    url: string,
    body: FormData,
    options?: ApiRequestOptions,
): Promise<T> => {
    return request<T>(url, {
        method: "POST",
        body,
    }, options);
};

export const apiPatch = <T, B = unknown>(
    url: string,
    body: B,
    options?: ApiRequestOptions,
): Promise<T> => {
    return request<T>(url, {
        method: "PATCH",
        body: JSON.stringify(body),
    }, options);
};

export const apiDelete = <T>(
    url: string,
    options?: ApiRequestOptions,
): Promise<T> => {
    return request<T>(url, {method: "DELETE"}, options);
};
