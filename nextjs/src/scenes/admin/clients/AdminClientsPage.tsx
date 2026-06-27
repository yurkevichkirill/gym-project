'use client'

import { useEffect, useMemo } from "react";
import Link from "next/link";
import { usePathname, useSearchParams } from "next/navigation";
import { observer } from "mobx-react-lite";
import {
    ADMIN_CLIENT_QUERY_KEYS,
    DEFAULT_ADMIN_CLIENTS_SORT,
    getAdminClientsRequestKey,
    parseAdminClientsListParams,
} from "@/api/admin/clients.api";
import { useStore } from "@/store/StoreProvider";
import EmptyState from "@/shared/ui/EmptyState";
import ErrorState from "@/shared/ui/ErrorState";
import LoadingState from "@/shared/ui/LoadingState";
import PaginationControls from "@/shared/ui/PaginationControls";
import AdminClientCard from "@/scenes/admin/clients/AdminClientCard";
import AdminClientCreateForm from "@/scenes/admin/clients/AdminClientCreateForm";
import AdminClientsFilters, { type AdminClientsFiltersForm, toAdminClientsFilterValues } from "@/scenes/admin/clients/AdminClientsFilters";
import AdminClientsImportForm from "@/scenes/admin/clients/AdminClientsImportForm";

const normalizeInteger = (value: string): string => value === "" ? "" : Number(value).toString();

const AdminClientsPage = observer(() => {
    const { adminClientsStore } = useStore();
    const pathname = usePathname();
    const searchParams = useSearchParams();
    const searchParamsString = searchParams.toString();
    const requestParams = useMemo(
        () => parseAdminClientsListParams(new URLSearchParams(searchParamsString)),
        [searchParamsString],
    );
    const requestKey = useMemo(() => getAdminClientsRequestKey(requestParams), [requestParams]);
    const formValues = useMemo(() => toAdminClientsFilterValues(requestParams), [requestParams]);

    useEffect(() => {
        void adminClientsStore.init(requestParams);
    }, [adminClientsStore, requestKey, requestParams]);

    const updateUrl = (nextSearchParams: URLSearchParams) => {
        const queryString = nextSearchParams.toString();
        window.history.pushState(null, "", `${pathname}${queryString ? `?${queryString}` : ""}`);
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const applyFilters = (values: AdminClientsFiltersForm) => {
        const next = new URLSearchParams(searchParamsString);
        const set = (key: string, value: string) => value === "" ? next.delete(key) : next.set(key, value);

        set("minAge", normalizeInteger(values.minAge));
        set("maxAge", normalizeInteger(values.maxAge));
        set("minBalance", normalizeInteger(values.minBalance));
        set("maxBalance", normalizeInteger(values.maxBalance));
        set("isDeleted", values.isDeleted);
        set("limit", normalizeInteger(values.limit));
        set("sort", values.sort === DEFAULT_ADMIN_CLIENTS_SORT ? "" : values.sort);
        next.delete("page");
        updateUrl(next);
    };

    const resetView = () => {
        const next = new URLSearchParams(searchParamsString);
        ADMIN_CLIENT_QUERY_KEYS.forEach((key) => next.delete(key));
        updateUrl(next);
    };

    const changePage = (page: number) => {
        const next = new URLSearchParams(searchParamsString);
        if (page <= 1) {
            next.delete("page");
        } else {
            next.set("page", page.toString());
        }
        updateUrl(next);
    };

    const hasResponse = adminClientsStore.loadedRequestKey === requestKey;
    const isFetching = adminClientsStore.isLoading || adminClientsStore.isRefreshing;
    const isInitialLoading = !hasResponse && isFetching;
    const pagination = hasResponse ? adminClientsStore.pagination : null;
    const hasQueryState = ADMIN_CLIENT_QUERY_KEYS.some((key) => searchParams.has(key));

    return (
        <section className="mx-auto w-full max-w-7xl">
            <div className="mb-8 flex flex-wrap items-end justify-between gap-5">
                <div className="max-w-3xl">
                    <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">Administration</p>
                    <h1 className="mt-2 text-3xl font-bold sm:text-4xl">Clients</h1>
                    <p className="mt-4 text-gray-600">
                        Manage client accounts, state transitions, visit write-offs and import queueing.
                    </p>
                </div>
                <Link href="/admin" className="rounded-md border border-gray-300 bg-white px-4 py-2 font-semibold transition hover:border-secondary-500">
                    Back to admin
                </Link>
            </div>

            {adminClientsStore.mutationError ? (
                <div role="alert" className="mb-6 rounded-xl bg-red-50 p-4 text-red-700">
                    {adminClientsStore.mutationError}
                </div>
            ) : null}

            <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(360px,0.65fr)]">
                <div>
                    <AdminClientsFilters values={formValues} onApply={applyFilters} onReset={resetView} />

                    {isInitialLoading ? (
                        <LoadingState title="Loading clients..." description="We are fetching client records." />
                    ) : null}

                    {!isInitialLoading && !hasResponse && adminClientsStore.errorStatus === 403 ? (
                        <EmptyState title="Access denied" description="Your account is not allowed to manage clients." />
                    ) : null}

                    {!isInitialLoading && !hasResponse && adminClientsStore.error && adminClientsStore.errorStatus !== 403 ? (
                        <ErrorState
                            title="Unable to load clients"
                            message={adminClientsStore.error}
                            isRetrying={isFetching}
                            onRetry={() => void adminClientsStore.init(requestParams)}
                        />
                    ) : null}

                    {hasResponse ? (
                        <div aria-busy={isFetching}>
                            <div className="mb-5 flex min-h-6 flex-wrap items-center justify-between gap-3">
                                <p className="text-sm text-gray-600">
                                    {pagination ? `${pagination.total} client${pagination.total === 1 ? "" : "s"} found` : null}
                                </p>
                                {isFetching ? <p role="status" className="text-sm font-semibold text-secondary-500">Refreshing clients...</p> : null}
                            </div>

                            {adminClientsStore.clients.length === 0 ? (
                                <EmptyState
                                    title="No clients found"
                                    description="Try changing the filters or return to the full client list."
                                    action={hasQueryState ? (
                                        <button type="button" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold" onClick={resetView}>
                                            Reset view
                                        </button>
                                    ) : undefined}
                                />
                            ) : (
                                <div className={isFetching ? "opacity-60 transition-opacity" : "transition-opacity"}>
                                    <div className="grid gap-5 md:grid-cols-2">
                                        {adminClientsStore.clients.map((client) => <AdminClientCard key={client.id} client={client} />)}
                                    </div>
                                </div>
                            )}

                            {pagination ? (
                                <div className="mt-10">
                                    <PaginationControls currentPage={pagination.page} totalPages={pagination.pages} disabled={isFetching || adminClientsStore.error !== null} onPageChange={changePage} />
                                </div>
                            ) : null}
                        </div>
                    ) : null}
                </div>
                <aside className="grid content-start gap-6">
                    <AdminClientCreateForm />
                    <AdminClientsImportForm />
                </aside>
            </div>
        </section>
    );
});

export default AdminClientsPage;
