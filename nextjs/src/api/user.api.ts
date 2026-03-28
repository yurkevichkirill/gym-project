import UserPersonalType from "@/types/user.type";
import {ApiResponse} from "@/types/api-response.type";
import {apiGet} from "@/lib/apiClient";

export const getMe = async (): Promise<UserPersonalType> => {
    const data = await apiGet<ApiResponse<UserPersonalType>>('/me');

    return data.data;
}