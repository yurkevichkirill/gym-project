import type { Metadata } from "next";
import { AdminBookingDetailsPage } from "@/scenes/admin/AdminDetailPages";

export const metadata: Metadata = {
    title: "Admin Booking details",
};

type PageProps = {
    params: Promise<{ id: string }>;
};

export default async function Page({ params }: PageProps) {
    const resolvedParams = await params;
    return <AdminBookingDetailsPage id={Number(resolvedParams.id)} />;
}
