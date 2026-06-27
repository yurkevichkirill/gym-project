import { apiGet, apiPost } from "@/lib/apiClient";
import MembershipType from "@/types/membership/membership.type";
import MembershipBuyType from "@/types/membership/membership-buy.type";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";
import { MembershipFreezeType } from "@/types/membership/membership-freeze.type";
import { MembershipUnfreezeType } from "@/types/membership/membership-unfreeze.type";
import { MembershipRenewType } from "@/types/membership/membership-renew.type";
import { MembershipTerminateType } from "@/types/membership/membership-terminate.type";
import { MembershipStatusEnum } from "@/types/membership/membership-status.enum";
import { MembershipsGetQueryParams } from "@/types/membership/memberships-get.type";

export const DEFAULT_MEMBERSHIPS_SORT = "startDate:ASC";

export const MEMBERSHIP_QUERY_KEYS = [
    "membershipPlanId",
    "status",
    "minVisits",
    "maxVisits",
    "sort",
    "page",
    "limit",
] as const satisfies readonly (keyof MembershipsGetQueryParams)[];

const MEMBERSHIP_SORT_FIELDS = new Set([
    "startDate",
    "endDate",
    "status",
    "visits",
    "membershipPlanId",
]);
const MEMBERSHIP_SORT_ORDERS = new Set(["ASC", "DESC"]);
const MEMBERSHIP_STATUSES = new Set<string>(Object.values(MembershipStatusEnum));

export type MembershipsListResponse = ApiCollectionResponse<MembershipType[]>;

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
            && MEMBERSHIP_SORT_FIELDS.has(field)
            && MEMBERSHIP_SORT_ORDERS.has(order);
    });
};

export const parseMembershipsListParams = (
    searchParams: SearchParamsReader,
): MembershipsGetQueryParams => {
    const status = searchParams.get("status");
    const sort = searchParams.get("sort");

    return {
        membershipPlanId: readInteger(searchParams.get("membershipPlanId"), 1),
        status: status !== null && MEMBERSHIP_STATUSES.has(status)
            ? status as MembershipStatusEnum
            : undefined,
        minVisits: readInteger(searchParams.get("minVisits"), 0),
        maxVisits: readInteger(searchParams.get("maxVisits"), 0),
        sort: sort !== null && isValidSort(sort) ? sort : undefined,
        page: readInteger(searchParams.get("page"), 1),
        limit: readInteger(searchParams.get("limit"), 1, 100),
    };
};

const createMembershipsSearchParams = (
    params: MembershipsGetQueryParams,
): URLSearchParams => {
    const searchParams = new URLSearchParams();

    MEMBERSHIP_QUERY_KEYS.forEach((key) => {
        const value = params[key];

        if (value !== undefined && value !== null && value !== "") {
            searchParams.set(key, value.toString());
        }
    });

    return searchParams;
};

export const getMembershipsRequestKey = (
    params: MembershipsGetQueryParams,
): string => {
    return createMembershipsSearchParams(params).toString();
};

export const getAllMemberships = async (
    params: MembershipsGetQueryParams = {},
): Promise<MembershipsListResponse> => {
    const queryString = getMembershipsRequestKey(params);

    return await apiGet<MembershipsListResponse>(
        `/me/memberships/${queryString ? `?${queryString}` : ""}`,
    );
};

export const getMembership = async (id: number): Promise<MembershipType> => {
    const response = await apiGet<ApiItemResponse<MembershipType>>(`/me/memberships/${id}/`);

    return response.data;
};

export const buyMembership = async ({ membershipPlanId }: MembershipBuyType): Promise<MembershipType> => {
    const response = await apiPost<ApiItemResponse<MembershipType>, MembershipBuyType>("/me/membership/", {
        membershipPlanId,
    });

    return response.data;
};

export const cancelMembership = async (id: number): Promise<MembershipType> => {
    const response = await apiPost<ApiItemResponse<MembershipType>>(`/me/memberships/${id}/cancel/`);

    return response.data;
};

export const freezeMembership = async ({ id }: MembershipFreezeType): Promise<MembershipType> => {
    const response = await apiPost<ApiItemResponse<MembershipType>>(`/me/memberships/${id}/freeze/`);

    return response.data;
};

export const unfreezeMembership = async ({ id }: MembershipUnfreezeType): Promise<MembershipType> => {
    const response = await apiPost<ApiItemResponse<MembershipType>>(`/me/memberships/${id}/unfreeze/`);

    return response.data;
};

export const renewMembership = async ({ id }: MembershipRenewType): Promise<MembershipType> => {
    const response = await apiPost<ApiItemResponse<MembershipType>>(`/me/memberships/${id}/renew/`);

    return response.data;
};

export const terminateMembership = async ({ id }: MembershipTerminateType): Promise<MembershipType> => {
    const response = await apiPost<ApiItemResponse<MembershipType>>(`/me/memberships/${id}/terminate/`);

    return response.data;
};
