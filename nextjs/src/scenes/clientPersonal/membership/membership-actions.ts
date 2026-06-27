import { MembershipStatusEnum } from "@/types/membership/membership-status.enum";

export type MembershipAction = "cancel" | "freeze" | "unfreeze" | "renew" | "terminate";

const ACTIONS_BY_STATUS: Record<MembershipStatusEnum, readonly MembershipAction[]> = {
    [MembershipStatusEnum.PENDING]: ["cancel"],
    [MembershipStatusEnum.ACTIVE]: ["freeze", "terminate"],
    [MembershipStatusEnum.FROZEN]: ["unfreeze", "terminate"],
    [MembershipStatusEnum.EXPIRED]: ["renew"],
    [MembershipStatusEnum.CANCELED_PAYMENT_FAILED]: ["renew"],
    [MembershipStatusEnum.CANCELED_BY_CLIENT]: ["renew"],
    [MembershipStatusEnum.CANCELED_BY_SYSTEM]: ["renew"],
};

export const getMembershipActions = (
    status: MembershipStatusEnum,
): readonly MembershipAction[] => {
    return ACTIONS_BY_STATUS[status];
};
