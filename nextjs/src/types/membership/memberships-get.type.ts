import { MembershipStatusEnum } from "./membership-status.enum";

export interface MembershipsGetQueryParams {
    membershipPlanId?: number;
    status?: MembershipStatusEnum;
    minVisits?: number;
    maxVisits?: number;
    sort?: string;
    page?: number;
    limit?: number;
}
