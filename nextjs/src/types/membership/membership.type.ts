import {MembershipPlanType} from "@/types/membership/membership-plan.type";
import PaymentType from "@/types/payment/payment.type";
import { MembershipStatusEnum } from "./membership-status.enum";

export default interface MembershipType {
    id: number,
    name: string,
    durationDays: number,
    sessionLimit: number | null,
    membershipPlan: MembershipPlanType,
    startDate: string,
    endDate: string,
    status: MembershipStatusEnum,
    visits: number,
    createdAt: string,
    frozenAt: string,
    payment: PaymentType
}