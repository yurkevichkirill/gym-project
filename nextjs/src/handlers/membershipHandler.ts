import {notify} from "@/lib/notify";
import {buyMembership} from "@/api/memberships.api";
import {clientStore} from "@/store/ClientStore";
import {authStore} from "@/store/AuthStore";

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
            clientStore.init(),
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