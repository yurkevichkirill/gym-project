import type { Metadata } from "next";
import { AdminMembershipDetailsPage } from "@/scenes/admin/AdminDetailPages";

export const metadata: Metadata = {
    title: "Admin Membership details",
};

type PageProps = {
    params: Promise<{ id: string }>;
};

export default async function Page({ params }: PageProps) {
    const resolvedParams = await params;
    return <AdminMembershipDetailsPage id={Number(resolvedParams.id)} />;
}
