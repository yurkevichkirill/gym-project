import { MembershipStatusEnum } from "@/types/membership/membership-status.enum";

export type MembershipAction = "cancel" | "freeze" | "unfreeze" | "renew" | "terminate";

const ACTIONS_BY_STATUS: Record<MembershipStatusEnum, readonly MembershipAction[]> = {
    [MembershipStatusEnum.PENDING]: ["cancel", "terminate"],
    [MembershipStatusEnum.ACTIVE]: ["freeze", "terminate"],
    [MembershipStatusEnum.FROZEN]: ["unfreeze", "terminate"],
    [MembershipStatusEnum.EXPIRED]: ["renew"],
    [MembershipStatusEnum.CANCELED_PAYMENT_FAILED]: ["renew", "terminate"],
    [MembershipStatusEnum.CANCELED_BY_CLIENT]: ["renew", "terminate"],
    [MembershipStatusEnum.CANCELED_BY_SYSTEM]: ["renew", "terminate"],
};

export const getMembershipActions = (
    status: MembershipStatusEnum,
): readonly MembershipAction[] => {
    return ACTIONS_BY_STATUS[status];
};
