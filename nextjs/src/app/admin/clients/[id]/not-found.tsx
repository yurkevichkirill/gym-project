import Link from "next/link";
import EmptyState from "@/shared/ui/EmptyState";

const AdminClientNotFound = () => (
    <main className="px-6 pt-32 pb-20">
        <EmptyState
            title="Client not found"
            description="The requested client identifier is invalid or the client is unavailable."
            action={<Link href="/admin/clients" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold">Back to clients</Link>}
        />
    </main>
);

export default AdminClientNotFound;
