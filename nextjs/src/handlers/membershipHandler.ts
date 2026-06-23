import {notify} from "@/lib/notify";
import {authStore} from "@/store/AuthStore";
import {buyMembership} from "@/api/client/memberships.api";
import {membershipStore} from "@/store/MembershipStore";
import {getErrorMessage} from "@/lib/getErrorMessage";

export const handleMembership = async (membershipPlanId: number) => {
    const toastId = notify.loading("Buying membership...");

    try {
        const res = await buyMembership({
            membershipPlanId,
        });

        const plan = res.membershipPlan;

        notify.success(
            "Membership activated",
            `${plan.name} • ${plan.durationDays} days • ${
                plan.sessionLimit ?? "Unlimited"
            }`,
            toastId
        );

        await Promise.all([
            membershipStore.init(),
            authStore.checkAuth(),
        ]);
    } catch (error: unknown) {
        notify.error(
            "Buying failed",
            getErrorMessage(error),
            toastId,
        );
    }
};
