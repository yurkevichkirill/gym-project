import type {ReactNode} from "react";

type EmptyStateProps = {
    title: string;
    description?: string;
    action?: ReactNode;
    className?: string;
};

const EmptyState = ({
    title,
    description,
    action,
    className = "",
}: EmptyStateProps) => {
    return (
        <section
            className={`mx-auto flex min-h-60 w-full max-w-3xl flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center ${className}`}
        >
            <h2 className="text-xl font-semibold">{title}</h2>
            {description ? (
                <p className="mt-2 max-w-xl text-gray-500">{description}</p>
            ) : null}
            {action ? <div className="mt-6">{action}</div> : null}
        </section>
    );
};

export default EmptyState;
