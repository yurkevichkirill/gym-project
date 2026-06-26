import Link from "next/link";
import EmptyState from "@/shared/ui/EmptyState";

const MembershipPlanNotFound = () => {
    return (
        <main className="px-6 pb-20 pt-32">
            <EmptyState
                title="Membership plan not found"
                description="The requested membership plan does not exist or is no longer available."
                action={(
                    <Link
                        href="/membership-plans"
                        className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white"
                    >
                        Back to membership plans
                    </Link>
                )}
            />
        </main>
    );
};

export default MembershipPlanNotFound;
