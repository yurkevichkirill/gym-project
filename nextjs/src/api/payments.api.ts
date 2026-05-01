import {ApiResponse} from "@/types/api-response.type";
import {apiGet} from "@/lib/apiClient";
import PaymentType from "@/types/payment.type";

export const getMyPayments = async (): Promise<PaymentType[]> => {
    const data = await apiGet<ApiResponse<PaymentType[]>>('/me/payments/');

    return data.data;
}