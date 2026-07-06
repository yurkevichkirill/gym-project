import type { Metadata } from "next";
import { AdminTrainingsPage } from "@/scenes/admin/AdminDomainListPages";

export const metadata: Metadata = {
    title: "Admin Trainings",
};

export default function Page() {
    return <AdminTrainingsPage />;
}
