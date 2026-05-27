import {apiGet, apiPost} from "@/lib/apiClient";
import PaymentType from "@/types/payment/payment.type";
import {ApiCollectionResponse} from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";

export const getMyPayments = async (): Promise<PaymentType[]> => {
    const data = await apiGet<ApiCollectionResponse<PaymentType[]>>('/me/payments/');

    return data.data;
}

export const createStripeIntent = async (paymentId: number): Promise<string> => {
    const response = await apiPost<ApiItemResponse<{ clientSecret: string }>>(`/payments/${paymentId}/intent/`);
    return response.data.clientSecret;
};