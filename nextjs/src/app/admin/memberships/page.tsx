import type { Metadata } from "next";
import { AdminMembershipsPage } from "@/scenes/admin/AdminDomainListPages";

export const metadata: Metadata = {
    title: "Admin Memberships",
};

export default function Page() {
    return <AdminMembershipsPage />;
}
