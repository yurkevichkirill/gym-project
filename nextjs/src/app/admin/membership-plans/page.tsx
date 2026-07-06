import type { Metadata } from "next";
import { AdminMembershipPlansPage } from "@/scenes/admin/AdminDomainListPages";

export const metadata: Metadata = {
    title: "Admin Membership plans",
};

export default function Page() {
    return <AdminMembershipPlansPage />;
}
