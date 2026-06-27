import { MembershipPlanType } from "@/types/membership/membership-plan.type";
import PaymentType from "@/types/payment/payment.type";
import { MembershipStatusEnum } from "./membership-status.enum";

export default interface MembershipType {
    id: number;
    name: string;
    durationDays: number;
    sessionLimit: number | null;
    membershipPlan: MembershipPlanType | null;
    startDate: string | null;
    endDate: string | null;
    status: MembershipStatusEnum;
    visits: number;
    createdAt: string;
    frozenAt: string | null;
    payment: PaymentType;
}
