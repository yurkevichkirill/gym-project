import UserPersonalType from "@/types/client/client.type";
import {apiGet, apiPatch} from "@/lib/apiClient";
import UserPersonalEditType from "@/types/client/client-edit.type";
import {ApiItemResponse} from "@/types/api-item-response.type";

export const getMe = async (): Promise<UserPersonalType> => {
    const data = await apiGet<ApiItemResponse<UserPersonalType>>('/me/');

    return data.data;
}

export const editMe = async ({ phone }: UserPersonalEditType): Promise<UserPersonalType> => {
    const data = await apiPatch<ApiItemResponse<UserPersonalType>>('/me/', {
        phone
    });

    return data.data;
}