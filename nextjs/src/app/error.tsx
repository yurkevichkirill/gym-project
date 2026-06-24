'use client'

import { useEffect } from "react";

const ErrorPage = ({
    error,
    reset,
}: {
    error: Error & { digest?: string };
    reset: () => void;
}) => {
    useEffect(() => {
        console.error(error);
    }, [error]);

    return (
        <main className="mx-auto flex min-h-[60vh] w-5/6 flex-col items-center justify-center gap-4 pt-32 text-center">
            <h1 className="text-3xl font-bold">Something went wrong</h1>
            <p>We could not load this page. Please try again.</p>
            <button
                type="button"
                onClick={reset}
                className="rounded-lg bg-primary-500 px-6 py-3 font-semibold text-white hover:bg-primary-300"
            >
                Try again
            </button>
        </main>
    );
};

export default ErrorPage;
