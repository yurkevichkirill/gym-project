import { notFound } from "next/navigation";
import MembershipDetails from "@/scenes/clientPersonal/membership/MembershipDetails";

type MembershipPageProps = {
    params: Promise<{ id: string }>;
};

const MembershipPage = async ({ params }: MembershipPageProps) => {
    const { id } = await params;

    if (!/^\d+$/.test(id)) {
        notFound();
    }

    const membershipId = Number(id);

    if (!Number.isSafeInteger(membershipId) || membershipId <= 0) {
        notFound();
    }

    return (
        <main className="px-6 pt-32 pb-20">
            <MembershipDetails membershipId={membershipId} />
        </main>
    );
};

export default MembershipPage;
