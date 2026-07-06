import type { Metadata } from "next";
import { AdminTrainingTypeDetailsPage } from "@/scenes/admin/AdminDetailPages";

export const metadata: Metadata = {
    title: "Admin Training type details",
};

type PageProps = {
    params: Promise<{ id: string }>;
};

export default async function Page({ params }: PageProps) {
    const resolvedParams = await params;
    return <AdminTrainingTypeDetailsPage id={Number(resolvedParams.id)} />;
}
