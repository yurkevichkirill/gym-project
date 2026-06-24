import {apiGet, apiPost} from "@/lib/apiClient";
import {
    CurrentUser,
    CurrentUserResponse,
    LoginRequest,
    LoginResponse,
} from "@/types/auth.type";

export const login = async (payload: LoginRequest): Promise<LoginResponse> => {
    return apiPost<LoginResponse, LoginRequest>(
        "/login/",
        payload,
        {skipAuthRefresh: true},
    );
};

export const getCurrentUser = async (): Promise<CurrentUser> => {
    const response = await apiGet<CurrentUserResponse>("/auth/me/");

    return response.data;
};

export const logout = async (): Promise<void> => {
    await apiPost<Record<string, never>, Record<string, never>>(
        "/logout/",
        {},
        {skipAuthRefresh: true},
    );
};
