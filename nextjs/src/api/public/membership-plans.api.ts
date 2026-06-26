import { MembershipPlanType } from "@/types/membership/membership-plan.type";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { ApiItemResponse } from "@/types/api-item-response.type";
import { publicApiGet } from "@/lib/publicApiClient";

export const DEFAULT_MEMBERSHIP_PLANS_SORT = "price:ASC";

const MEMBERSHIP_PLAN_SORT_FIELDS = new Set([
    "durationDays",
    "price",
    "sessionLimit",
]);
const SORT_ORDERS = new Set(["ASC", "DESC"]);

export type MembershipPlansListParams = {
    minDurationDays?: number;
    maxDurationDays?: number;
    minSessionLimit?: number;
    maxSessionLimit?: number;
    minPrice?: number;
    maxPrice?: number;
    isUnlimited?: boolean;
    sort?: string;
    page?: number;
    limit?: number;
};

export type MembershipPlansListResponse = ApiCollectionResponse<MembershipPlanType[]>;

type SearchParamsReader = {
    get: (name: string) => string | null;
};

type PublicRequestOptions = {
    signal?: AbortSignal;
};

const readNonNegativeInteger = (
    value: string | null,
    maximum?: number,
): number | undefined => {
    if (value === null || !/^\d+$/.test(value)) {
        return undefined;
    }

    const parsed = Number(value);

    if (!Number.isSafeInteger(parsed) || parsed < 0 || (maximum !== undefined && parsed > maximum)) {
        return undefined;
    }

    return parsed;
};

const readPositiveInteger = (
    value: string | null,
    maximum?: number,
): number | undefined => {
    const parsed = readNonNegativeInteger(value, maximum);

    return parsed !== undefined && parsed > 0 ? parsed : undefined;
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
        const parts = item.split(":");

        if (parts.length > 2) {
            return false;
        }

        const field = parts[0];
        const order = (parts[1] ?? "ASC").trim().toUpperCase();

        return MEMBERSHIP_PLAN_SORT_FIELDS.has(field) && SORT_ORDERS.has(order);
    });
};

export const parseMembershipPlansListParams = (
    searchParams: SearchParamsReader,
): MembershipPlansListParams => {
    const sort = searchParams.get("sort");

    return {
        minDurationDays: readNonNegativeInteger(searchParams.get("minDurationDays")),
        maxDurationDays: readNonNegativeInteger(searchParams.get("maxDurationDays")),
        minSessionLimit: readNonNegativeInteger(searchParams.get("minSessionLimit")),
        maxSessionLimit: readNonNegativeInteger(searchParams.get("maxSessionLimit")),
        minPrice: readNonNegativeInteger(searchParams.get("minPrice")),
        maxPrice: readNonNegativeInteger(searchParams.get("maxPrice")),
        isUnlimited: readBoolean(searchParams.get("isUnlimited")),
        sort: sort && isValidSort(sort) ? sort : undefined,
        page: readPositiveInteger(searchParams.get("page")),
        limit: readPositiveInteger(searchParams.get("limit"), 100),
    };
};

const createMembershipPlansSearchParams = (
    params: MembershipPlansListParams,
): URLSearchParams => {
    const searchParams = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined) {
            searchParams.set(key, value.toString());
        }
    });

    return searchParams;
};

export const getMembershipPlansRequestKey = (
    params: MembershipPlansListParams,
): string => {
    return createMembershipPlansSearchParams(params).toString();
};

export const getMembershipPlansPage = async (
    params: MembershipPlansListParams = {},
    options: PublicRequestOptions = {},
): Promise<MembershipPlansListResponse> => {
    const queryString = getMembershipPlansRequestKey(params);

    return await publicApiGet<MembershipPlansListResponse>(
        `/membership/plans/${queryString ? `?${queryString}` : ""}`,
        { signal: options.signal },
    );
};

export const getMembershipPlans = async (): Promise<MembershipPlanType[]> => {
    const response = await getMembershipPlansPage();

    return response.data;
};

export const getMembershipPlan = async (
    id: string,
    options: PublicRequestOptions = {},
): Promise<MembershipPlanType> => {
    const response = await publicApiGet<ApiItemResponse<MembershipPlanType>>(
        `/membership/plans/${id}/`,
        { signal: options.signal },
    );

    return response.data;
};
