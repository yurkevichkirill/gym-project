import {apiGet} from "@/lib/apiClient";
import PaymentType from "@/types/payment/payment.type";
import {ApiCollectionResponse} from "@/types/api-collection-response";

export const getMyPayments = async (): Promise<PaymentType[]> => {
    const data = await apiGet<ApiCollectionResponse<PaymentType[]>>('/me/payments/');

    return data.data;
}