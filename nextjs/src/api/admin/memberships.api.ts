import { apiGet, apiPost } from "@/lib/apiClient";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";
import { MembershipStatusEnum } from "@/types/membership/membership-status.enum";
import {
    AdminMembership,
    AdminMembershipCreateRequest,
    AdminMembershipsGetQueryParams,
} from "@/types/admin/admin-membership.type";
import {
    createAdminSearchParams,
    getAdminRequestKey,
    readEnum,
    readNonNegativeInteger,
    readPositiveInteger,
    readSort,
} from "@/api/admin/admin-api-utils";
import type { SearchParamsReader } from "@/types/admin/admin-common.type";

export const DEFAULT_ADMIN_MEMBERSHIPS_SORT = "startDate:ASC";
const SORT_FIELDS = ["startDate", "endDate", "status", "visits", "membershipPlanId"] as const;
const STATUS_VALUES = Object.values(MembershipStatusEnum);

export type AdminMembershipsListResponse = ApiCollectionResponse<AdminMembership[]>;

export const parseAdminMembershipsListParams = (searchParams: SearchParamsReader): AdminMembershipsGetQueryParams => ({
    membershipPlanId: readPositiveInteger(searchParams.get("membershipPlanId")),
    clientId: readPositiveInteger(searchParams.get("clientId")),
    status: readEnum(searchParams.get("status"), STATUS_VALUES),
    minVisits: readNonNegativeInteger(searchParams.get("minVisits")),
    maxVisits: readNonNegativeInteger(searchParams.get("maxVisits")),
    sort: readSort(searchParams, SORT_FIELDS),
    page: readPositiveInteger(searchParams.get("page")),
    limit: readPositiveInteger(searchParams.get("limit"), 100),
});

export const getAdminMembershipsRequestKey = (params: AdminMembershipsGetQueryParams = {}): string => (
    getAdminRequestKey(params)
);

export const getAdminMemberships = async (
    params: AdminMembershipsGetQueryParams = {},
): Promise<AdminMembershipsListResponse> => {
    const queryString = createAdminSearchParams(params).toString();

    return await apiGet<AdminMembershipsListResponse>(`/memberships/${queryString ? `?${queryString}` : ""}`);
};

export const getAdminMembership = async (id: number): Promise<AdminMembership> => {
    const response = await apiGet<ApiItemResponse<AdminMembership>>(`/memberships/${id}/`);

    return response.data;
};

export const createAdminClientMembership = async (
    clientId: number,
    payload: AdminMembershipCreateRequest,
): Promise<AdminMembership> => {
    const response = await apiPost<ApiItemResponse<AdminMembership>, AdminMembershipCreateRequest>(
        `/clients/${clientId}/membership/`,
        payload,
    );

    return response.data;
};

const membershipAction = async (id: number, action: string): Promise<AdminMembership> => {
    const response = await apiPost<ApiItemResponse<AdminMembership>>(`/memberships/${id}/${action}/`);

    return response.data;
};

export const cancelAdminMembership = (id: number): Promise<AdminMembership> => membershipAction(id, "cancel");
export const freezeAdminMembership = (id: number): Promise<AdminMembership> => membershipAction(id, "freeze");
export const unfreezeAdminMembership = (id: number): Promise<AdminMembership> => membershipAction(id, "unfreeze");
export const renewAdminMembership = (id: number): Promise<AdminMembership> => membershipAction(id, "renew");
export const terminateAdminMembership = (id: number): Promise<AdminMembership> => membershipAction(id, "terminate");

