import { MembershipStatusEnum } from "@/types/membership/membership-status.enum";
import MembershipType from "@/types/membership/membership.type";

export type AdminMembership = MembershipType;

export interface AdminMembershipsGetQueryParams {
    membershipPlanId?: number;
    clientId?: number;
    status?: MembershipStatusEnum;
    minVisits?: number;
    maxVisits?: number;
    sort?: string;
    page?: number;
    limit?: number;
}

export interface AdminMembershipCreateRequest {
    membershipPlanId: number;
}

