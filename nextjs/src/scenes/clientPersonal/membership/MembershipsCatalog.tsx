'use client'

import { useEffect, useMemo } from "react";
import Link from "next/link";
import { usePathname, useSearchParams } from "next/navigation";
import { observer } from "mobx-react-lite";
import {
    DEFAULT_MEMBERSHIPS_SORT,
    getMembershipsRequestKey,
    MEMBERSHIP_QUERY_KEYS,
    parseMembershipsListParams,
} from "@/api/client/memberships.api";
import { useStore } from "@/store/StoreProvider";
import EmptyState from "@/shared/ui/EmptyState";
import ErrorState from "@/shared/ui/ErrorState";
import LoadingState from "@/shared/ui/LoadingState";
import PaginationControls from "@/shared/ui/PaginationControls";
import MembershipCatalogCard from "@/scenes/clientPersonal/membership/MembershipCatalogCard";
import MembershipsFilters, {
    type MembershipsFiltersForm,
    toMembershipsFilterValues,
} from "@/scenes/clientPersonal/membership/MembershipsFilters";

const normalizeInteger = (value: string): string => value === "" ? "" : Number(value).toString();

const MembershipsCatalog = observer(() => {
    const { membershipStore } = useStore();
    const pathname = usePathname();
    const searchParams = useSearchParams();
    const searchParamsString = searchParams.toString();
    const requestParams = useMemo(
        () => parseMembershipsListParams(new URLSearchParams(searchParamsString)),
        [searchParamsString],
    );
    const requestKey = useMemo(
        () => getMembershipsRequestKey(requestParams),
        [requestParams],
    );
    const formValues = useMemo(
        () => toMembershipsFilterValues(requestParams),
        [requestParams],
    );

    useEffect(() => {
        void membershipStore.init(
            parseMembershipsListParams(new URLSearchParams(requestKey)),
        );
    }, [membershipStore, requestKey]);

    const updateUrl = (nextSearchParams: URLSearchParams) => {
        const queryString = nextSearchParams.toString();
        window.history.pushState(null, "", `${pathname}${queryString ? `?${queryString}` : ""}`);
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const applyFilters = (values: MembershipsFiltersForm) => {
        const next = new URLSearchParams(searchParamsString);
        const set = (key: string, value: string) => value === ""
            ? next.delete(key)
            : next.set(key, value);

        set("membershipPlanId", normalizeInteger(values.membershipPlanId));
        set("status", values.status);
        set("minVisits", normalizeInteger(values.minVisits));
        set("maxVisits", normalizeInteger(values.maxVisits));
        set("limit", normalizeInteger(values.limit));
        set("sort", values.sort === DEFAULT_MEMBERSHIPS_SORT ? "" : values.sort);
        next.delete("page");
        updateUrl(next);
    };

    const resetView = () => {
        const next = new URLSearchParams(searchParamsString);
        MEMBERSHIP_QUERY_KEYS.forEach((key) => next.delete(key));
        updateUrl(next);
    };

    const changePage = (page: number) => {
        const next = new URLSearchParams(searchParamsString);
        page <= 1 ? next.delete("page") : next.set("page", page.toString());
        updateUrl(next);
    };

    const hasResponse = membershipStore.loadedRequestKey === requestKey;
    const isFetching = membershipStore.isLoading || membershipStore.isRefreshing;
    const isInitialLoading = !hasResponse && isFetching;
    const pagination = hasResponse ? membershipStore.pagination : null;
    const hasQueryState = MEMBERSHIP_QUERY_KEYS.some((key) => searchParams.has(key));

    return (
        <section className="mx-auto w-full max-w-6xl">
            <div className="mb-8 flex flex-wrap items-end justify-between gap-5">
                <div className="max-w-3xl">
                    <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                        Client cabinet
                    </p>
                    <h1 className="mt-2 text-3xl font-bold sm:text-4xl">My memberships</h1>
                    <p className="mt-4 text-gray-600">
                        Filter and review your complete membership history. Statuses, sorting, and pagination come directly from the server.
                    </p>
                </div>
                <Link
                    href="/me"
                    className="rounded-md border border-gray-300 bg-white px-4 py-2 font-semibold transition hover:border-secondary-500"
                >
                    Back to cabinet
                </Link>
            </div>

            <MembershipsFilters
                values={formValues}
                onApply={applyFilters}
                onReset={resetView}
            />

            {isInitialLoading ? (
                <LoadingState
                    title="Loading memberships..."
                    description="We are fetching your membership history."
                />
            ) : null}

            {!isInitialLoading && !hasResponse && membershipStore.errorStatus === 403 ? (
                <EmptyState
                    title="Access denied"
                    description="Your account is not allowed to view these memberships."
                    action={(
                        <Link href="/me" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold">
                            Back to cabinet
                        </Link>
                    )}
                />
            ) : null}

            {!isInitialLoading && !hasResponse && membershipStore.error && membershipStore.errorStatus !== 403 ? (
                <ErrorState
                    title="Unable to load memberships"
                    message={membershipStore.error}
                    isRetrying={isFetching}
                    onRetry={() => void membershipStore.init(requestParams)}
                />
            ) : null}

            {hasResponse ? (
                <div aria-busy={isFetching}>
                    <div className="mb-5 flex min-h-6 flex-wrap items-center justify-between gap-3">
                        <p className="text-sm text-gray-600">
                            {pagination
                                ? `${pagination.total} membership${pagination.total === 1 ? "" : "s"} found`
                                : null}
                        </p>
                        {isFetching ? (
                            <p
                                role="status"
                                aria-live="polite"
                                className="text-sm font-semibold text-secondary-500"
                            >
                                Refreshing memberships...
                            </p>
                        ) : null}
                    </div>

                    {membershipStore.error ? (
                        <div
                            role="alert"
                            className="mb-6 flex flex-col gap-3 rounded-xl bg-red-50 p-4 text-red-700 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <p>
                                {membershipStore.errorStatus === 403
                                    ? "Access to the requested membership list was denied."
                                    : membershipStore.error}
                            </p>
                            <button
                                type="button"
                                className="self-start rounded-md border border-red-300 bg-white px-4 py-2 font-semibold disabled:opacity-50 sm:self-auto"
                                disabled={isFetching}
                                onClick={() => void membershipStore.init(requestParams)}
                            >
                                {isFetching ? "Retrying..." : "Retry"}
                            </button>
                        </div>
                    ) : null}

                    {membershipStore.memberships.length === 0 ? (
                        <EmptyState
                            title="No memberships found"
                            description="Try changing the filters or return to your complete membership history."
                            action={hasQueryState ? (
                                <button
                                    type="button"
                                    className="rounded-md bg-secondary-500 px-5 py-2 font-semibold"
                                    onClick={resetView}
                                >
                                    Reset view
                                </button>
                            ) : undefined}
                        />
                    ) : (
                        <div className={isFetching ? "opacity-60 transition-opacity" : "transition-opacity"}>
                            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                {membershipStore.memberships.map((membership) => (
                                    <MembershipCatalogCard key={membership.id} membership={membership} />
                                ))}
                            </div>
                        </div>
                    )}

                    {pagination ? (
                        <div className="mt-10">
                            <PaginationControls
                                currentPage={pagination.page}
                                totalPages={pagination.pages}
                                disabled={isFetching || membershipStore.error !== null}
                                onPageChange={changePage}
                            />
                        </div>
                    ) : null}
                </div>
            ) : null}
        </section>
    );
});

export default MembershipsCatalog;
