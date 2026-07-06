import { ReactNode } from "react";

export const primaryActionClassName = "inline-flex min-h-11 items-center justify-center rounded-xl bg-secondary-500 px-5 py-2.5 text-sm font-semibold text-gray-900 transition hover:bg-primary-500 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 disabled:cursor-not-allowed disabled:opacity-50";
export const secondaryActionClassName = "inline-flex min-h-10 items-center justify-center rounded-xl border border-gray-100 bg-white px-4 py-2 text-sm font-semibold text-gray-500 transition hover:border-primary-300 hover:bg-primary-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500";
export const previewCardClassName = "rounded-2xl border border-gray-100 bg-white p-4 text-gray-900 shadow-sm transition hover:border-primary-300 hover:shadow-md focus-within:border-primary-300 focus-within:shadow-md";
export const statusBadgeClassName = "inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold leading-5";
export const loadingStateClassName = "min-h-20 rounded-2xl border border-dashed border-gray-100 bg-gray-20/60 p-5 text-sm text-gray-600";
export const emptyStateClassName = "rounded-2xl border border-dashed border-gray-100 bg-gray-20/70 p-5 text-sm text-gray-600";
export const errorStateClassName = "rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800";
export const successStateClassName = "rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800";

type SectionProps = {
    title: string;
    children: ReactNode;
    action?: ReactNode;
    description?: string;
    className?: string;
    titleId?: string;
};

const Section = ({
    title,
    children,
    action,
    description,
    className = "",
    titleId,
}: SectionProps) => (
    <section
        className={`rounded-3xl border border-gray-100 bg-white/95 p-5 shadow-sm sm:p-6 ${className}`}
        aria-labelledby={titleId}
    >
        <div className="mb-5 flex flex-col gap-3 border-b border-gray-50 pb-4 sm:flex-row sm:items-start sm:justify-between">
            <div className="min-w-0">
                <div className="flex items-center gap-3">
                    <span className="h-7 w-1 rounded-full bg-secondary-500" aria-hidden="true" />
                    <h3 id={titleId} className="text-xl font-bold text-gray-500">
                        {title}
                    </h3>
                </div>
                {description ? (
                    <p className="mt-2 text-sm text-gray-600">{description}</p>
                ) : null}
            </div>

            {action ? (
                <div className="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">
                    {action}
                </div>
            ) : null}
        </div>

        {children}
    </section>
);

export default Section;
