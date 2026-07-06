import type {Metadata} from "next";
import AdminHomePage from "@/scenes/admin/AdminHomePage";

export const metadata: Metadata = {
    title: "Admin cabinet",
};

const AdminPage = () => {
    return <AdminHomePage />;
};

export default AdminPage;
