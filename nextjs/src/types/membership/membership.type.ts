import {MembershipPlanType} from "@/types/membership/membership-plan.type";

export default interface MembershipType {
    id: number,
    membershipPlan: MembershipPlanType,
    startDate: string,
    endDate: string,
    status: string,
    visits: number,
    createdAt: string,
}