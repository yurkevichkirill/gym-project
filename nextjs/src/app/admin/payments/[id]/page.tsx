import type { Metadata } from "next";
import { AdminPaymentDetailsPage } from "@/scenes/admin/AdminDetailPages";

export const metadata: Metadata = {
    title: "Admin Payment details",
};

type PageProps = {
    params: Promise<{ id: string }>;
};

export default async function Page({ params }: PageProps) {
    const resolvedParams = await params;
    return <AdminPaymentDetailsPage id={Number(resolvedParams.id)} />;
}
