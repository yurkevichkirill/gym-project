import type { Metadata } from "next";
import { AdminPaymentsPage } from "@/scenes/admin/AdminDomainListPages";

export const metadata: Metadata = {
    title: "Admin Payments",
};

export default function Page() {
    return <AdminPaymentsPage />;
}
