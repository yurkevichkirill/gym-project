import ClientType from "@/types/client/client.type";
import {apiDelete, apiGet, apiPatch, apiPost} from "@/lib/apiClient";
import ClientEditType from "@/types/client/client-edit.type";
import {ApiItemResponse} from "@/types/api-item-response.type";
import MembershipType from "@/types/membership/membership.type";
import TopupBalanceType from "@/types/client/topupBalance.type";
import PaymentType from "@/types/payment/payment.type";
import ClientActivateType from "@/types/client/clientActivate.type";

export const get = async (): Promise<ClientType> => {
    const data = await apiGet<ApiItemResponse<ClientType>>('/me/');

    return data.data;
}

export const update = async ({ phone }: ClientEditType): Promise<ClientType> => {
    const data = await apiPatch<ApiItemResponse<ClientType>>('/me/', {
        phone
    });

    return data.data;
}

export const remove = async () => {
    return apiDelete<null>('/me/');
}

export const visit = async (): Promise<MembershipType> => {
    const data = await apiPost<ApiItemResponse<MembershipType>>('/me/visit/');

    return data.data;
}

export const topUpBalance = async ({ amount }: TopupBalanceType): Promise<PaymentType> => {
    const data = await apiPost<ApiItemResponse<PaymentType>>('/me/topup/', {
        amount
    });

    return data.data;
}

export const activate = async ({ activationToken, password }: ClientActivateType): Promise<ClientType> => {
    const data = await apiPatch<ApiItemResponse<ClientType>>('/me/activate', {
        activationToken,
        password,
    });

    return data.data;
}