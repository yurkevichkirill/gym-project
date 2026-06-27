import { apiGet, apiPost } from "@/lib/apiClient";
import PaymentType from "@/types/payment/payment.type";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";
import { PaymentStatusEnum } from "@/types/payment/payment-status.enum";
import { PaymentsGetQueryParams } from "@/types/payment/payments-get.type";

export const PaymentScope = {
    CLIENT: "client",
    TRAINER: "trainer",
} as const;

export type PaymentScope = typeof PaymentScope[keyof typeof PaymentScope];

export const DEFAULT_PAYMENTS_SORT = "createdAt:DESC";

export const CLIENT_PAYMENT_QUERY_KEYS = [
    "trainerId",
    "minAmount",
    "maxAmount",
    "isRefund",
    "status",
    "minCreatedAt",
    "maxCreatedAt",
    "sort",
    "page",
    "limit",
] as const satisfies readonly (keyof PaymentsGetQueryParams)[];

export const TRAINER_PAYMENT_QUERY_KEYS = [
    "clientId",
    "minAmount",
    "maxAmount",
    "isRefund",
    "status",
    "minCreatedAt",
    "maxCreatedAt",
    "sort",
    "page",
    "limit",
] as const satisfies readonly (keyof PaymentsGetQueryParams)[];

export const PAYMENT_QUERY_KEYS = CLIENT_PAYMENT_QUERY_KEYS;

const PAYMENT_SORT_FIELDS = new Set([
    "amount",
    "category",
    "paidAt",
    "status",
    "isRefund",
    "createdAt",
]);
const PAYMENT_SORT_ORDERS = new Set(["ASC", "DESC"]);
const PAYMENT_STATUSES = new Set<string>(Object.values(PaymentStatusEnum));

export type PaymentsListResponse = ApiCollectionResponse<PaymentType[]>;

type SearchParamsReader = {
    get: (name: string) => string | null;
};

const readInteger = (
    value: string | null,
    minimum: number,
    maximum?: number,
): number | undefined => {
    if (value === null || !/^\d+$/.test(value)) {
        return undefined;
    }

    const parsed = Number(value);

    if (
        !Number.isSafeInteger(parsed)
        || parsed < minimum
        || (maximum !== undefined && parsed > maximum)
    ) {
        return undefined;
    }

    return parsed;
};

const readBoolean = (value: string | null): boolean | undefined => {
    if (value === "true") {
        return true;
    }

    if (value === "false") {
        return false;
    }

    return undefined;
};

const readDate = (value: string | null): string | undefined => {
    if (value === null || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return undefined;
    }

    const parsed = new Date(`${value}T00:00:00Z`);

    return Number.isNaN(parsed.getTime()) || parsed.toISOString().slice(0, 10) !== value
        ? undefined
        : value;
};

const isValidSort = (value: string): boolean => {
    return value.split(",").every((item) => {
        const parts = item.trim().split(":");

        if (parts.length > 2) {
            return false;
        }

        const field = parts[0]?.trim();
        const order = (parts[1] ?? "ASC").trim().toUpperCase();

        return field !== undefined
            && field.length > 0
            && PAYMENT_SORT_FIELDS.has(field)
            && PAYMENT_SORT_ORDERS.has(order);
    });
};

export const getPaymentQueryKeys = (
    scope: PaymentScope,
): readonly (keyof PaymentsGetQueryParams)[] => {
    return scope === PaymentScope.TRAINER
        ? TRAINER_PAYMENT_QUERY_KEYS
        : CLIENT_PAYMENT_QUERY_KEYS;
};

export const parsePaymentsListParams = (
    searchParams: SearchParamsReader,
    scope: PaymentScope = PaymentScope.CLIENT,
): PaymentsGetQueryParams => {
    const status = searchParams.get("status");
    const sort = searchParams.get("sort");

    return {
        ...(scope === PaymentScope.TRAINER
            ? { clientId: readInteger(searchParams.get("clientId"), 1) }
            : { trainerId: readInteger(searchParams.get("trainerId"), 1) }),
        minAmount: readInteger(searchParams.get("minAmount"), 1),
        maxAmount: readInteger(searchParams.get("maxAmount"), 1),
        isRefund: readBoolean(searchParams.get("isRefund")),
        status: status !== null && PAYMENT_STATUSES.has(status)
            ? status as PaymentStatusEnum
            : undefined,
        minCreatedAt: readDate(searchParams.get("minCreatedAt")),
        maxCreatedAt: readDate(searchParams.get("maxCreatedAt")),
        sort: sort !== null && isValidSort(sort) ? sort : undefined,
        page: readInteger(searchParams.get("page"), 1),
        limit: readInteger(searchParams.get("limit"), 1, 100),
    };
};

const createPaymentsSearchParams = (
    params: PaymentsGetQueryParams,
    scope: PaymentScope,
): URLSearchParams => {
    const searchParams = new URLSearchParams();

    getPaymentQueryKeys(scope).forEach((key) => {
        const value = params[key];

        if (value !== undefined && value !== null && value !== "") {
            searchParams.set(key, value.toString());
        }
    });

    return searchParams;
};

export const getPaymentsRequestKey = (
    params: PaymentsGetQueryParams,
    scope: PaymentScope = PaymentScope.CLIENT,
): string => {
    return `${scope}:${createPaymentsSearchParams(params, scope).toString()}`;
};

export const getPaymentsForScope = async (
    params: PaymentsGetQueryParams = {},
    scope: PaymentScope = PaymentScope.CLIENT,
): Promise<PaymentsListResponse> => {
    const queryString = createPaymentsSearchParams(params, scope).toString();
    const endpoint = scope === PaymentScope.TRAINER
        ? "/trainer/payments/"
        : "/me/payments/";

    return await apiGet<PaymentsListResponse>(
        `${endpoint}${queryString ? `?${queryString}` : ""}`,
    );
};

export const getPaymentForScope = async (
    id: number,
    scope: PaymentScope = PaymentScope.CLIENT,
): Promise<PaymentType> => {
    const endpoint = scope === PaymentScope.TRAINER
        ? `/trainer/payments/${id}/`
        : `/me/payments/${id}/`;
    const response = await apiGet<ApiItemResponse<PaymentType>>(endpoint);

    return response.data;
};

export const getMyPayments = async (
    params: PaymentsGetQueryParams = {},
): Promise<PaymentsListResponse> => {
    return await getPaymentsForScope(params, PaymentScope.CLIENT);
};

export const getMyPayment = async (id: number): Promise<PaymentType> => {
    return await getPaymentForScope(id, PaymentScope.CLIENT);
};

export const createStripeIntent = async (paymentId: number): Promise<string> => {
    const response = await apiPost<ApiItemResponse<{ clientSecret: string }>>(
        `/payments/${paymentId}/intent/`,
    );

    return response.data.clientSecret;
};
