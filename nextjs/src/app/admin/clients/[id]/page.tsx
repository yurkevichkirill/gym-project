import { notFound } from "next/navigation";
import AdminClientDetailsPage from "@/scenes/admin/clients/AdminClientDetailsPage";

type AdminClientRouteProps = {
    params: Promise<{ id: string }>;
};

const AdminClientRoute = async ({ params }: AdminClientRouteProps) => {
    const { id } = await params;

    if (!/^\d+$/.test(id)) {
        notFound();
    }

    const clientId = Number(id);

    if (!Number.isSafeInteger(clientId) || clientId <= 0) {
        notFound();
    }

    return (
        <main className="px-6 pt-32 pb-20">
            <AdminClientDetailsPage clientId={clientId} />
        </main>
    );
};

export default AdminClientRoute;
