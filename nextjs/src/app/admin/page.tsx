import type {Metadata} from "next";
import Link from "next/link";

export const metadata: Metadata = {
    title: "Admin cabinet",
};

const AdminPage = () => {
    return (
        <main className="px-6 pt-32 pb-20">
            <section className="mx-auto w-full max-w-5xl rounded-2xl bg-white p-8 shadow-sm sm:p-10">
                <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                    Administration
                </p>
                <h1 className="mt-2 text-3xl font-bold sm:text-4xl">
                    Admin cabinet
                </h1>
                <p className="mt-4 max-w-2xl text-gray-600">
                    Your protected workspace for gym administration.
                </p>
                <div className="mt-8">
                    <Link
                        href="/admin/clients"
                        className="inline-flex rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white"
                    >
                        Manage clients
                    </Link>
                </div>
            </section>
        </main>
    );
};

export default AdminPage;
