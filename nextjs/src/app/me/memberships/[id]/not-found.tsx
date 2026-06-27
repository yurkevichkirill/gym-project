import Link from "next/link";
import EmptyState from "@/shared/ui/EmptyState";

const MembershipNotFound = () => (
    <main className="px-6 pt-32 pb-20">
        <EmptyState
            title="Membership not found"
            description="The requested membership identifier is invalid or the membership is unavailable."
            action={(
                <Link
                    href="/me/memberships"
                    className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white"
                >
                    Back to memberships
                </Link>
            )}
        />
    </main>
);

export default MembershipNotFound;
