import Link from "next/link";
import { AdminShell, adminNavItems } from "@/scenes/admin/shared/AdminShell";
import { previewCardClassName, primaryActionClassName } from "@/shared/Section";

const AdminHomePage = () => (
    <AdminShell
        title="Admin cabinet"
        description="Protected workspace for administrative operations exposed by the Symfony API."
    >
        <section className="grid gap-4 sm:grid-cols-2">
            {adminNavItems.map((item) => (
                <article key={item.href} className={previewCardClassName}>
                    <h2 className="text-lg font-bold text-gray-500">{item.label}</h2>
                    <p className="mt-2 min-h-12 text-sm text-gray-600">{item.description}</p>
                    <Link href={item.href} className={primaryActionClassName + " mt-4 w-full sm:w-auto"}>
                        Open
                    </Link>
                </article>
            ))}
        </section>
    </AdminShell>
);

export default AdminHomePage;
