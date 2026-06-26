import {apiDelete, apiGet, apiPatch} from "@/lib/apiClient";
import {ApiItemResponse} from "@/types/api-item-response.type";
import ClientEditType from "@/types/client/client-edit.type";
import ClientType from "@/types/client/client.type";

export const getCurrentClient = async (): Promise<ClientType> => {
    const response = await apiGet<ApiItemResponse<ClientType>>("/me/");

    return response.data;
};

export const updateCurrentClient = async (payload: ClientEditType): Promise<ClientType> => {
    const response = await apiPatch<ApiItemResponse<ClientType>, ClientEditType>(
        "/me/",
        payload,
    );

    return response.data;
};

export const deleteCurrentClient = async (): Promise<void> => {
    await apiDelete<null>("/me/");
};
