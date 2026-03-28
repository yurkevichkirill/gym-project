import {ApiResponse} from "@/types/api-response.type";
import {apiGet} from "@/lib/apiClient";
import MembershipType from "@/types/membership.type";

export const getMyMemberships = async (): Promise<MembershipType[]> => {
    const data = await apiGet<ApiResponse<MembershipType[]>>('/me/memberships');

    return data.data;
}