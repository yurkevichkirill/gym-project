'use client'

type PaginationControlsProps = {
    currentPage: number;
    totalPages: number;
    onPageChange: (page: number) => void;
    disabled?: boolean;
};

const PaginationControls = ({
    currentPage,
    totalPages,
    onPageChange,
    disabled = false,
}: PaginationControlsProps) => {
    if (totalPages <= 1) {
        return null;
    }

    const normalizedTotalPages = Math.max(1, totalPages);
    const normalizedCurrentPage = Math.min(
        Math.max(1, currentPage),
        normalizedTotalPages,
    );
    const canGoBack = !disabled && normalizedCurrentPage > 1;
    const canGoForward = !disabled && normalizedCurrentPage < normalizedTotalPages;

    return (
        <nav
            className="flex flex-wrap items-center justify-center gap-4"
            aria-label="Pagination"
        >
            <button
                type="button"
                className="rounded-md border border-gray-300 bg-white px-4 py-2 font-semibold transition hover:border-secondary-500 disabled:cursor-not-allowed disabled:opacity-50"
                disabled={!canGoBack}
                onClick={() => onPageChange(normalizedCurrentPage - 1)}
            >
                Previous
            </button>
            <span className="text-sm text-gray-600" aria-live="polite">
                Page {normalizedCurrentPage} of {normalizedTotalPages}
            </span>
            <button
                type="button"
                className="rounded-md border border-gray-300 bg-white px-4 py-2 font-semibold transition hover:border-secondary-500 disabled:cursor-not-allowed disabled:opacity-50"
                disabled={!canGoForward}
                onClick={() => onPageChange(normalizedCurrentPage + 1)}
            >
                Next
            </button>
        </nav>
    );
};

export default PaginationControls;
