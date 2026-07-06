import type { Metadata } from "next";
import { AdminTrainingTypesPage } from "@/scenes/admin/AdminDomainListPages";

export const metadata: Metadata = {
    title: "Admin Training types",
};

export default function Page() {
    return <AdminTrainingTypesPage />;
}
