import type {Metadata} from "next";

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
            </section>
        </main>
    );
};

export default AdminPage;
