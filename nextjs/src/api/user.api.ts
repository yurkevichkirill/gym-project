import UserPersonalType from "@/types/user/user.type";
import {ApiResponse} from "@/types/api-response.type";
import {apiGet, apiPatch} from "@/lib/apiClient";
import UserPersonalEditType from "@/types/user/user-edit.type";

export const getMe = async (): Promise<UserPersonalType> => {
    const data = await apiGet<ApiResponse<UserPersonalType>>('/me/');

    return data.data;
}

export const editMe = async ({ phone }: UserPersonalEditType): Promise<UserPersonalType> => {
    const data = await apiPatch<ApiResponse<UserPersonalType>>('/me/', {
        phone
    });

    return data.data;
}