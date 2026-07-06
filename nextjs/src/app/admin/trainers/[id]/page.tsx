import type { Metadata } from "next";
import { AdminTrainerDetailsPage } from "@/scenes/admin/AdminDetailPages";

export const metadata: Metadata = {
    title: "Admin Trainer details",
};

type PageProps = {
    params: Promise<{ id: string }>;
};

export default async function Page({ params }: PageProps) {
    const resolvedParams = await params;
    return <AdminTrainerDetailsPage id={Number(resolvedParams.id)} />;
}
