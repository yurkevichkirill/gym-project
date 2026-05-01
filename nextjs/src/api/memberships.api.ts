import {ApiResponse} from "@/types/api-response.type";
import {apiGet, apiPost} from "@/lib/apiClient";
import MembershipType from "@/types/membership/membership.type";
import MembershipCreateType from "@/types/membership/membership-create.type";

export const getMyMemberships = async (): Promise<MembershipType[]> => {
    const data = await apiGet<ApiResponse<MembershipType[]>>('/me/memberships/');

    return data.data;
}

export const buyMembership = async ({ membershipPlanId }: MembershipCreateType): Promise<MembershipType> => {
    const data = await apiPost<ApiResponse<MembershipType>, MembershipCreateType>("/me/membership/", {
        membershipPlanId,
    });

    return data.data;
}