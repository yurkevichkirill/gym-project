import { MembershipPlanType } from "@/types/membership/membership-plan.type";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { publicApiGet } from "@/lib/publicApiClient";

export const getMembershipPlans = async (): Promise<MembershipPlanType[]> => {
    const response = await publicApiGet<ApiCollectionResponse<MembershipPlanType[]>>('/membership/plans/');

    return response.data;
};
