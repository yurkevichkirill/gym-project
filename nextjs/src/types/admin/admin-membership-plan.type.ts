import { MembershipPlanType } from "@/types/membership/membership-plan.type";

export type AdminMembershipPlan = MembershipPlanType;

export interface AdminMembershipPlanCreateRequest {
    name: string;
    durationDays: number;
    sessionLimit?: number | null;
    price: number;
}

export interface AdminMembershipPlanUpdateRequest {
    name?: string;
    durationDays?: number;
    sessionLimit?: number | null;
    price?: number;
}

