'use client'

import { useEffect, useMemo } from "react";
import Link from "next/link";
import { usePathname, useSearchParams } from "next/navigation";
import { observer } from "mobx-react-lite";
import {
    BOOKING_QUERY_KEYS,
    DEFAULT_BOOKINGS_SORT,
    getBookingsRequestKey,
    parseBookingsListParams,
} from "@/api/client/bookings.api";
import { useStore } from "@/store/StoreProvider";
import EmptyState from "@/shared/ui/EmptyState";
import ErrorState from "@/shared/ui/ErrorState";
import LoadingState from "@/shared/ui/LoadingState";
import PaginationControls from "@/shared/ui/PaginationControls";
import BookingCatalogCard from "@/scenes/clientPersonal/bookings/BookingCatalogCard";
import BookingCreateForm from "@/scenes/clientPersonal/bookings/BookingCreateForm";
import BookingsFilters, {
    type BookingsFiltersForm,
    toBookingsFilterValues,
} from "@/scenes/clientPersonal/bookings/BookingsFilters";

const normalizeInteger = (value: string): string => value === "" ? "" : Number(value).toString();

const BookingsCatalog = observer(() => {
    const { bookingStore } = useStore();
    const pathname = usePathname();
    const searchParams = useSearchParams();
    const searchParamsString = searchParams.toString();
    const requestParams = useMemo(
        () => parseBookingsListParams(new URLSearchParams(searchParamsString)),
        [searchParamsString],
    );
    const requestKey = useMemo(() => getBookingsRequestKey(requestParams), [requestParams]);
    const formValues = useMemo(() => toBookingsFilterValues(requestParams), [requestParams]);

    useEffect(() => {
        void bookingStore.init(parseBookingsListParams(new URLSearchParams(requestKey)));
    }, [bookingStore, requestKey]);

    const updateUrl = (nextSearchParams: URLSearchParams) => {
        const queryString = nextSearchParams.toString();
        window.history.pushState(null, "", `${pathname}${queryString ? `?${queryString}` : ""}`);
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const applyFilters = (values: BookingsFiltersForm) => {
        const next = new URLSearchParams(searchParamsString);
        const set = (key: string, value: string) => value === "" ? next.delete(key) : next.set(key, value);

        set("trainerId", normalizeInteger(values.trainerId));
        set("status", values.status);
        set("date", values.date);
        set("startTime", values.startTime === "" ? "" : `${values.startTime}:00`);
        set("durationMinutes", normalizeInteger(values.durationMinutes));
        set("limit", normalizeInteger(values.limit));
        set("sort", values.sort === DEFAULT_BOOKINGS_SORT ? "" : values.sort);
        next.delete("page");
        updateUrl(next);
    };

    const resetView = () => {
        const next = new URLSearchParams(searchParamsString);
        BOOKING_QUERY_KEYS.forEach((key) => next.delete(key));
        updateUrl(next);
    };

    const changePage = (page: number) => {
        const next = new URLSearchParams(searchParamsString);
        page <= 1 ? next.delete("page") : next.set("page", page.toString());
        updateUrl(next);
    };

    const hasResponse = bookingStore.loadedRequestKey !== null;
    const isFetching = bookingStore.isLoading || bookingStore.isRefreshing;
    const isInitialLoading = !hasResponse && isFetching;
    const pagination = bookingStore.pagination;
    const hasQueryState = BOOKING_QUERY_KEYS.some((key) => searchParams.has(key));

    return (
        <section className="mx-auto w-full max-w-6xl">
            <div className="mb-8 flex flex-wrap items-end justify-between gap-5">
                <div className="max-w-3xl">
                    <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">Client cabinet</p>
                    <h1 className="mt-2 text-3xl font-bold sm:text-4xl">My bookings</h1>
                    <p className="mt-4 text-gray-600">Create a booking, then filter and review your complete booking history. Results are sorted and paginated by the server.</p>
                </div>
                <Link href="/me" className="rounded-md border border-gray-300 bg-white px-4 py-2 font-semibold transition hover:border-secondary-500">Back to cabinet</Link>
            </div>

            <BookingCreateForm />

            <BookingsFilters values={formValues} onApply={applyFilters} onReset={resetView} />

            {isInitialLoading ? <LoadingState title="Loading bookings..." description="We are fetching your booking history." /> : null}

            {!isInitialLoading && !hasResponse && bookingStore.errorStatus === 403 ? (
                <EmptyState title="Access denied" description="Your account is not allowed to view these bookings." action={<Link href="/me" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold">Back to cabinet</Link>} />
            ) : null}

            {!isInitialLoading && !hasResponse && bookingStore.error && bookingStore.errorStatus !== 403 ? (
                <ErrorState title="Unable to load bookings" message={bookingStore.error} isRetrying={isFetching} onRetry={() => void bookingStore.init(requestParams)} />
            ) : null}

            {hasResponse ? (
                <div aria-busy={isFetching}>
                    <div className="mb-5 flex min-h-6 flex-wrap items-center justify-between gap-3">
                        <p className="text-sm text-gray-600">{pagination ? `${pagination.total} booking${pagination.total === 1 ? "" : "s"} found` : null}</p>
                        {isFetching ? <p role="status" aria-live="polite" className="text-sm font-semibold text-secondary-500">Refreshing bookings...</p> : null}
                    </div>

                    {bookingStore.error ? (
                        <div role="alert" className="mb-6 flex flex-col gap-3 rounded-xl bg-red-50 p-4 text-red-700 sm:flex-row sm:items-center sm:justify-between">
                            <p>{bookingStore.errorStatus === 403 ? "Access to the requested booking list was denied." : bookingStore.error}</p>
                            <button type="button" className="self-start rounded-md border border-red-300 bg-white px-4 py-2 font-semibold disabled:opacity-50 sm:self-auto" disabled={isFetching} onClick={() => void bookingStore.init(requestParams)}>{isFetching ? "Retrying..." : "Retry"}</button>
                        </div>
                    ) : null}

                    {bookingStore.bookings.length === 0 ? (
                        <EmptyState title="No bookings found" description="Try changing the filters or return to your complete booking history." action={hasQueryState ? <button type="button" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold" onClick={resetView}>Reset view</button> : undefined} />
                    ) : (
                        <div className={isFetching ? "opacity-60 transition-opacity" : "transition-opacity"}>
                            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                {bookingStore.bookings.map((booking) => <BookingCatalogCard key={booking.id} booking={booking} />)}
                            </div>
                        </div>
                    )}

                    {pagination ? (
                        <div className="mt-10">
                            <PaginationControls currentPage={pagination.page} totalPages={pagination.pages} disabled={isFetching || bookingStore.error !== null} onPageChange={changePage} />
                        </div>
                    ) : null}
                </div>
            ) : null}
        </section>
    );
});

export default BookingsCatalog;
