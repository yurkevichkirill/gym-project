'use client'

type ErrorStateProps = {
    message: string;
    title?: string;
    retryLabel?: string;
    isRetrying?: boolean;
    onRetry: () => void;
    className?: string;
};

const ErrorState = ({
    message,
    title = "Something went wrong",
    retryLabel = "Retry",
    isRetrying = false,
    onRetry,
    className = "",
}: ErrorStateProps) => {
    return (
        <section
            className={`mx-auto flex min-h-60 w-full max-w-3xl flex-col items-center justify-center rounded-2xl bg-white px-6 py-10 text-center shadow-sm ${className}`}
            role="alert"
        >
            <h2 className="text-xl font-semibold">{title}</h2>
            <p className="mt-2 max-w-xl text-gray-500">{message}</p>
            <button
                type="button"
                className="mt-6 rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                disabled={isRetrying}
                onClick={onRetry}
            >
                {isRetrying ? "Retrying..." : retryLabel}
            </button>
        </section>
    );
};

export default ErrorState;
