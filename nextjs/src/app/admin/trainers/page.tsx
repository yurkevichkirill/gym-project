import type { Metadata } from "next";
import { AdminTrainersPage } from "@/scenes/admin/AdminDomainListPages";

export const metadata: Metadata = {
    title: "Admin Trainers",
};

export default function Page() {
    return <AdminTrainersPage />;
}
