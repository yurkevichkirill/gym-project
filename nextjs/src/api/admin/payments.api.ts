import { apiGet } from "@/lib/apiClient";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";
import { PaymentStatusEnum } from "@/types/payment/payment-status.enum";
import { AdminPayment, AdminPaymentsGetQueryParams } from "@/types/admin/admin-payment.type";
import {
    createAdminSearchParams,
    getAdminRequestKey,
    readBoolean,
    readDateTimeLocal,
    readEnum,
    readNonNegativeInteger,
    readPositiveInteger,
    readSort,
} from "@/api/admin/admin-api-utils";
import type { SearchParamsReader } from "@/types/admin/admin-common.type";

export const DEFAULT_ADMIN_PAYMENTS_SORT = "createdAt:DESC";
const SORT_FIELDS = ["amount", "category", "paidAt", "status", "isRefund", "createdAt"] as const;
const STATUS_VALUES = Object.values(PaymentStatusEnum);

export type AdminPaymentsListResponse = ApiCollectionResponse<AdminPayment[]>;

export const parseAdminPaymentsListParams = (searchParams: SearchParamsReader): AdminPaymentsGetQueryParams => ({
    trainerId: readPositiveInteger(searchParams.get("trainerId")),
    clientId: readPositiveInteger(searchParams.get("clientId")),
    minAmount: readNonNegativeInteger(searchParams.get("minAmount")),
    maxAmount: readNonNegativeInteger(searchParams.get("maxAmount")),
    isRefund: readBoolean(searchParams.get("isRefund")),
    status: readEnum(searchParams.get("status"), STATUS_VALUES),
    minCreatedAt: readDateTimeLocal(searchParams.get("minCreatedAt")),
    maxCreatedAt: readDateTimeLocal(searchParams.get("maxCreatedAt")),
    sort: readSort(searchParams, SORT_FIELDS),
    page: readPositiveInteger(searchParams.get("page")),
    limit: readPositiveInteger(searchParams.get("limit"), 100),
});

export const getAdminPaymentsRequestKey = (params: AdminPaymentsGetQueryParams = {}): string => (
    getAdminRequestKey(params)
);

export const getAdminPayments = async (
    params: AdminPaymentsGetQueryParams = {},
): Promise<AdminPaymentsListResponse> => {
    const queryString = createAdminSearchParams(params).toString();

    return await apiGet<AdminPaymentsListResponse>(`/payments/${queryString ? `?${queryString}` : ""}`);
};

export const getAdminPayment = async (id: number): Promise<AdminPayment> => {
    const response = await apiGet<ApiItemResponse<AdminPayment>>(`/payments/${id}/`);

    return response.data;
};

