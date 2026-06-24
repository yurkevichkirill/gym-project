import Link from "next/link";

const NotFound = () => {
    return (
        <main className="mx-auto flex min-h-[60vh] w-5/6 flex-col items-center justify-center gap-4 pt-32 text-center">
            <h1 className="text-4xl font-bold">Page not found</h1>
            <p>The requested trainer or page does not exist.</p>
            <Link
                href="/"
                className="rounded-lg bg-primary-500 px-6 py-3 font-semibold text-white hover:bg-primary-300"
            >
                Back to home
            </Link>
        </main>
    );
};

export default NotFound;
