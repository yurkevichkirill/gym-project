import type { Metadata } from "next";
import { AdminBookingsPage } from "@/scenes/admin/AdminDomainListPages";

export const metadata: Metadata = {
    title: "Admin Bookings",
};

export default function Page() {
    return <AdminBookingsPage />;
}
