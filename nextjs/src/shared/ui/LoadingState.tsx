type LoadingStateProps = {
    title?: string;
    description?: string;
    className?: string;
};

const LoadingState = ({
    title = "Loading...",
    description,
    className = "",
}: LoadingStateProps) => {
    return (
        <section
            className={`mx-auto flex min-h-60 w-full max-w-3xl flex-col items-center justify-center px-6 text-center ${className}`}
            role="status"
            aria-live="polite"
        >
            <span
                className="h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-secondary-500"
                aria-hidden="true"
            />
            <h2 className="mt-5 text-xl font-semibold">{title}</h2>
            {description ? (
                <p className="mt-2 max-w-xl text-gray-500">{description}</p>
            ) : null}
        </section>
    );
};

export default LoadingState;
