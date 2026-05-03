import {MembershipPlanType} from "@/types/membership/membership-plan.type";
import {ApiCollectionResponse} from "@/types/api-collection-response";

export const getMembershipPlans = async (): Promise<MembershipPlanType[]> => {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/membership/plans/`);
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }
    const obj: ApiCollectionResponse<MembershipPlanType[]> = await response.json();

    return obj.data;
}