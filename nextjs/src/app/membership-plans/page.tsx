import type { Metadata } from "next";
import { Suspense } from "react";
import MembershipPlansCatalog from "@/scenes/membershipPlans/MembershipPlansCatalog";
import LoadingState from "@/shared/ui/LoadingState";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
    title: "Membership Plans",
};

const MembershipPlansPage = () => {
    return (
        <main className="px-6 pb-20 pt-32">
            <Suspense
                fallback={(
                    <LoadingState
                        title="Loading membership plans..."
                        description="We are preparing the membership catalog."
                    />
                )}
            >
                <MembershipPlansCatalog />
            </Suspense>
        </main>
    );
};

export default MembershipPlansPage;
