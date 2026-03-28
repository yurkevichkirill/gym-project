import {ApiResponse} from "@/types/api-response.type";
import {MembershipPlanType} from "@/types/membership-plan.type";

export const getMembershipPlans = async (): Promise<MembershipPlanType[]> => {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/membership/plans/`);
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }
    const obj: ApiResponse<MembershipPlanType[]> = await response.json();

    return obj.data;
}