import {apiGet, apiPost} from "@/lib/apiClient";
import MembershipType from "@/types/membership/membership.type";
import MembershipCreateType from "@/types/membership/membership-create.type";
import {ApiCollectionResponse} from "@/types/api-collection-response";
import {ApiItemResponse} from "@/types/api-item-response.type";

export const getMyMemberships = async (): Promise<MembershipType[]> => {
    const data = await apiGet<ApiCollectionResponse<MembershipType[]>>('/me/memberships/');

    return data.data;
}

export const buyMembership = async ({ membershipPlanId }: MembershipCreateType): Promise<MembershipType> => {
    const data = await apiPost<ApiItemResponse<MembershipType>, MembershipCreateType>("/me/membership/", {
        membershipPlanId,
    });

    return data.data;
}