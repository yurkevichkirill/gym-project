import {notify} from "@/lib/notify";
import {authStore} from "@/store/AuthStore";
import {buyMembership} from "@/api/client/memberships.api";
import {membershipStore} from "@/store/MembershipStore";

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
        ])

    } catch (error: any) {
        notify.error(
            "Buying failed",
            error?.message || "Something went wrong",
            toastId,
        );
    }
}