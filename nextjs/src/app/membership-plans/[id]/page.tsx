import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { getMembershipPlan } from "@/api/public/membership-plans.api";
import { ApiClientError } from "@/lib/apiClient";
import MembershipPlanDetails from "@/scenes/membershipPlans/MembershipPlanDetails";
import type { MembershipPlanType } from "@/types/membership/membership-plan.type";

type Props = {
    params: Promise<{ id: string }>;
};

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
    title: "Membership Plan Details",
};

const MembershipPlanPage = async ({ params }: Props) => {
    const { id } = await params;
    const parsedId = Number(id);

    if (!/^\d+$/.test(id) || !Number.isSafeInteger(parsedId) || parsedId <= 0) {
        notFound();
    }

    let membershipPlan: MembershipPlanType;

    try {
        membershipPlan = await getMembershipPlan(id);
    } catch (error: unknown) {
        if (error instanceof ApiClientError && error.status === 404) {
            notFound();
        }

        throw error;
    }

    return (
        <main className="px-6 pb-20 pt-32">
            <MembershipPlanDetails membershipPlan={membershipPlan} />
        </main>
    );
};

export default MembershipPlanPage;
