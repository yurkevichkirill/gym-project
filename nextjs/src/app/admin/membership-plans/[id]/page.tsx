import type { Metadata } from "next";
import { AdminMembershipPlanDetailsPage } from "@/scenes/admin/AdminDetailPages";

export const metadata: Metadata = {
    title: "Admin Membership plan details",
};

type PageProps = {
    params: Promise<{ id: string }>;
};

export default async function Page({ params }: PageProps) {
    const resolvedParams = await params;
    return <AdminMembershipPlanDetailsPage id={Number(resolvedParams.id)} />;
}
