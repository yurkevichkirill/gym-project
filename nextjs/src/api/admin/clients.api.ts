import { apiDelete, apiGet, apiPatch, apiPost } from "@/lib/apiClient";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";
import type {
    AdminClient,
    AdminClientCreateRequest,
    AdminClientImportRequest,
    AdminClientImportResponse,
    AdminClientsGetQueryParams,
    AdminClientUpdateRequest,
} from "@/types/admin/admin-client.type";
import MembershipType from "@/types/membership/membership.type";

export const DEFAULT_ADMIN_CLIENTS_SORT = "age:ASC";

export const ADMIN_CLIENT_QUERY_KEYS = [
    "minAge",
    "maxAge",
    "minBalance",
    "maxBalance",
    "isDeleted",
    "sort",
    "page",
    "limit",
] as const satisfies readonly (keyof AdminClientsGetQueryParams)[];

const SORT_FIELDS = new Set(["firstName", "lastName", "balance", "age", "createdAt", "updatedAt", "deletedAt"]);
const SORT_ORDERS = new Set(["ASC", "DESC"]);

export type AdminClientsListResponse = ApiCollectionResponse<AdminClient[]>;

type SearchParamsReader = {
    get: (name: string) => string | null;
};

const readInteger = (value: string | null, minimum: number, maximum?: number): number | undefined => {
    if (value === null || !/^\d+$/.test(value)) {
        return undefined;
    }

    const parsed = Number(value);

    if (!Number.isSafeInteger(parsed) || parsed < minimum || (maximum !== undefined && parsed > maximum)) {
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

const isValidSort = (value: string): boolean => {
    return value.split(",").every((item) => {
        const parts = item.trim().split(":");

        if (parts.length > 2) {
            return false;
        }

        const field = parts[0]?.trim();
        const order = (parts[1] ?? "ASC").trim().toUpperCase();

        return field !== undefined && field.length > 0 && SORT_FIELDS.has(field) && SORT_ORDERS.has(order);
    });
};

export const parseAdminClientsListParams = (searchParams: SearchParamsReader): AdminClientsGetQueryParams => {
    const sort = searchParams.get("sort");

    return {
        minAge: readInteger(searchParams.get("minAge"), 1),
        maxAge: readInteger(searchParams.get("maxAge"), 1),
        minBalance: readInteger(searchParams.get("minBalance"), 1),
        maxBalance: readInteger(searchParams.get("maxBalance"), 1),
        isDeleted: readBoolean(searchParams.get("isDeleted")),
        sort: sort !== null && isValidSort(sort) ? sort : undefined,
        page: readInteger(searchParams.get("page"), 1),
        limit: readInteger(searchParams.get("limit"), 1, 100),
    };
};

const createSearchParams = (params: AdminClientsGetQueryParams): URLSearchParams => {
    const searchParams = new URLSearchParams();

    ADMIN_CLIENT_QUERY_KEYS.forEach((key) => {
        const value = params[key];

        if (value !== undefined && value !== null && value !== "") {
            searchParams.set(key, value.toString());
        }
    });

    return searchParams;
};

export const getAdminClientsRequestKey = (params: AdminClientsGetQueryParams = {}): string => {
    return createSearchParams(params).toString();
};

export const getAdminClients = async (params: AdminClientsGetQueryParams = {}): Promise<AdminClientsListResponse> => {
    const queryString = createSearchParams(params).toString();

    return await apiGet<AdminClientsListResponse>(`/clients/${queryString ? `?${queryString}` : ""}`);
};

export const getAdminClient = async (id: number): Promise<AdminClient> => {
    const response = await apiGet<ApiItemResponse<AdminClient>>(`/clients/${id}/`);

    return response.data;
};

export const createAdminClient = async (payload: AdminClientCreateRequest): Promise<AdminClient> => {
    const response = await apiPost<ApiItemResponse<AdminClient>, AdminClientCreateRequest>("/clients/", payload);

    return response.data;
};

export const updateAdminClient = async (id: number, payload: AdminClientUpdateRequest): Promise<AdminClient> => {
    const response = await apiPatch<ApiItemResponse<AdminClient>, AdminClientUpdateRequest>(`/clients/${id}/`, payload);

    return response.data;
};

export const deleteAdminClient = async (id: number): Promise<void> => {
    await apiDelete<null>(`/clients/${id}/`);
};

export const restoreAdminClient = async (id: number): Promise<AdminClient> => {
    const response = await apiPost<ApiItemResponse<AdminClient>>(`/clients/${id}/restore/`);

    return response.data;
};

export const blockAdminClient = async (id: number): Promise<AdminClient> => {
    const response = await apiPost<ApiItemResponse<AdminClient>>(`/clients/${id}/block/`);

    return response.data;
};

export const unblockAdminClient = async (id: number): Promise<AdminClient> => {
    const response = await apiPost<ApiItemResponse<AdminClient>>(`/clients/${id}/unblock/`);

    return response.data;
};

export const registerAdminClientVisit = async (id: number): Promise<MembershipType> => {
    const response = await apiPost<ApiItemResponse<MembershipType>>(`/clients/${id}/visit/`);

    return response.data;
};

export const importAdminClients = async (payload: AdminClientImportRequest): Promise<AdminClientImportResponse> => {
    const response = await apiPost<ApiItemResponse<AdminClientImportResponse>, AdminClientImportRequest>(
        "/import/clients/",
        payload,
    );

    return response.data;
};
