import { apiPost } from "@/lib/apiClient";
import { ApiItemResponse } from "@/types/api-item-response.type";
import TopupBalanceType from "@/types/client/topupBalance.type";
import PaymentType from "@/types/payment/payment.type";

export const topUpBalance = async ({ amount }: TopupBalanceType): Promise<PaymentType> => {
    const response = await apiPost<ApiItemResponse<PaymentType>, TopupBalanceType>('/me/topup/', {
        amount,
    });

    return response.data;
};
