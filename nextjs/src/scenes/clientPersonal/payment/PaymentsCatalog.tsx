'use client'

import { useEffect, useMemo } from "react";
import Link from "next/link";
import { usePathname, useSearchParams } from "next/navigation";
import { observer } from "mobx-react-lite";
import {
    DEFAULT_PAYMENTS_SORT,
    getPaymentQueryKeys,
    getPaymentsRequestKey,
    parsePaymentsListParams,
    PaymentScope,
} from "@/api/client/payments.api";
import { useStore } from "@/store/StoreProvider";
import EmptyState from "@/shared/ui/EmptyState";
import ErrorState from "@/shared/ui/ErrorState";
import LoadingState from "@/shared/ui/LoadingState";
import PaginationControls from "@/shared/ui/PaginationControls";
import PaymentCatalogCard from "@/scenes/clientPersonal/payment/PaymentCatalogCard";
import PaymentsFilters, {
    type PaymentsFiltersForm,
    toPaymentsFilterValues,
} from "@/scenes/clientPersonal/payment/PaymentsFilters";
import { Roles } from "@/types/auth.type";

const normalizeInteger = (value: string): string => value === "" ? "" : Number(value).toString();

type PaymentsCatalogProps = {
    forcedScope?: PaymentScope;
};

const PaymentsCatalog = observer(({ forcedScope }: PaymentsCatalogProps) => {
    const { authStore, paymentStore } = useStore();
    const pathname = usePathname();
    const searchParams = useSearchParams();
    const searchParamsString = searchParams.toString();
    const scope = forcedScope ?? (authStore.user?.roles.includes(Roles.TRAINER)
        ? PaymentScope.TRAINER
        : PaymentScope.CLIENT);
    const queryKeys = getPaymentQueryKeys(scope);
    const requestParams = useMemo(
        () => parsePaymentsListParams(new URLSearchParams(searchParamsString), scope),
        [scope, searchParamsString],
    );
    const requestKey = useMemo(
        () => getPaymentsRequestKey(requestParams, scope),
        [requestParams, scope],
    );
    const formValues = useMemo(
        () => toPaymentsFilterValues(requestParams, scope),
        [requestParams, scope],
    );
    const isTrainerScope = scope === PaymentScope.TRAINER;
    const relatedUserLabel = isTrainerScope ? "client" : "trainer";
    const relatedUserTitle = isTrainerScope ? "Client" : "Trainer";
    const paymentsBasePath = isTrainerScope ? "/me/trainer-payments" : "/me/payments";

    useEffect(() => {
        void paymentStore.init(requestParams, scope);
    }, [paymentStore, requestKey, requestParams, scope]);

    const updateUrl = (nextSearchParams: URLSearchParams) => {
        const queryString = nextSearchParams.toString();
        window.history.pushState(null, "", `${pathname}${queryString ? `?${queryString}` : ""}`);
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const applyFilters = (values: PaymentsFiltersForm) => {
        const next = new URLSearchParams(searchParamsString);
        const relatedUserKey = isTrainerScope ? "clientId" : "trainerId";
        const unrelatedUserKey = isTrainerScope ? "trainerId" : "clientId";
        const set = (key: string, value: string) => value === ""
            ? next.delete(key)
            : next.set(key, value);

        next.delete(unrelatedUserKey);
        set(relatedUserKey, normalizeInteger(values.relatedUserId));
        set("minAmount", normalizeInteger(values.minAmount));
        set("maxAmount", normalizeInteger(values.maxAmount));
        set("isRefund", values.isRefund);
        set("status", values.status);
        set("minCreatedAt", values.minCreatedAt);
        set("maxCreatedAt", values.maxCreatedAt);
        set("limit", normalizeInteger(values.limit));
        set("sort", values.sort === DEFAULT_PAYMENTS_SORT ? "" : values.sort);
        next.delete("page");
        updateUrl(next);
    };

    const resetView = () => {
        const next = new URLSearchParams(searchParamsString);
        queryKeys.forEach((key) => next.delete(key));
        next.delete("trainerId");
        next.delete("clientId");
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

    const hasResponse = paymentStore.loadedRequestKey === requestKey;
    const isFetching = paymentStore.isLoading || paymentStore.isRefreshing;
    const isInitialLoading = !hasResponse && isFetching;
    const pagination = hasResponse ? paymentStore.pagination : null;
    const hasQueryState = queryKeys.some((key) => searchParams.has(key));

    return (
        <section className="mx-auto w-full max-w-6xl">
            <div className="mb-8 flex flex-wrap items-end justify-between gap-5">
                <div className="max-w-3xl">
                    <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                        {isTrainerScope ? "Trainer cabinet" : "Client cabinet"}
                    </p>
                    <h1 className="mt-2 text-3xl font-bold sm:text-4xl">
                        {isTrainerScope ? "Trainer payments" : "My payments"}
                    </h1>
                    <p className="mt-4 text-gray-600">
                        Review {isTrainerScope ? "payments assigned to your trainer account" : "your complete payment history"} using server-side filters, sorting, and pagination.
                    </p>
                </div>
                <Link
                    href="/me"
                    className="rounded-md border border-gray-300 bg-white px-4 py-2 font-semibold transition hover:border-secondary-500"
                >
                    Back to cabinet
                </Link>
            </div>

            {isTrainerScope ? (
                <div
                    role="note"
                    className="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
                >
                    The trainer payment API accepts a client ID filter, but its PaymentResponseDTO does not expose client identity on returned payment items. Client values therefore cannot be displayed without a backend contract change.
                </div>
            ) : null}

            <PaymentsFilters
                scope={scope}
                values={formValues}
                onApply={applyFilters}
                onReset={resetView}
            />

            {isInitialLoading ? (
                <LoadingState
                    title="Loading payments..."
                    description={`We are fetching ${isTrainerScope ? "trainer" : "your"} payment history.`}
                />
            ) : null}

            {!isInitialLoading && !hasResponse && paymentStore.errorStatus === 403 ? (
                <EmptyState
                    title="Access denied"
                    description="Your account is not allowed to view these payments."
                    action={(
                        <Link href="/me" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold">
                            Back to cabinet
                        </Link>
                    )}
                />
            ) : null}

            {!isInitialLoading && !hasResponse && paymentStore.errorStatus === 404 ? (
                <EmptyState
                    title={`${relatedUserTitle} not found`}
                    description={`The requested ${relatedUserLabel} ID does not exist. Change the filter and try again.`}
                    action={(
                        <button
                            type="button"
                            className="rounded-md bg-secondary-500 px-5 py-2 font-semibold"
                            onClick={resetView}
                        >
                            Reset view
                        </button>
                    )}
                />
            ) : null}

            {!isInitialLoading
                && !hasResponse
                && paymentStore.error
                && paymentStore.errorStatus !== 403
                && paymentStore.errorStatus !== 404 ? (
                    <ErrorState
                        title="Unable to load payments"
                        message={paymentStore.error}
                        isRetrying={isFetching}
                        onRetry={() => void paymentStore.init(requestParams, scope)}
                    />
                ) : null}

            {hasResponse ? (
                <div aria-busy={isFetching}>
                    <div className="mb-5 flex min-h-6 flex-wrap items-center justify-between gap-3">
                        <p className="text-sm text-gray-600">
                            {pagination
                                ? `${pagination.total} payment${pagination.total === 1 ? "" : "s"} found`
                                : null}
                        </p>
                        {isFetching ? (
                            <p
                                role="status"
                                aria-live="polite"
                                className="text-sm font-semibold text-secondary-500"
                            >
                                Refreshing payments...
                            </p>
                        ) : null}
                    </div>

                    {paymentStore.error ? (
                        <div
                            role="alert"
                            className="mb-6 flex flex-col gap-3 rounded-xl bg-red-50 p-4 text-red-700 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <p>
                                {paymentStore.errorStatus === 403
                                    ? "Access to the requested payment list was denied."
                                    : paymentStore.errorStatus === 404
                                        ? `The requested ${relatedUserLabel} no longer exists.`
                                        : paymentStore.error}
                            </p>
                            <button
                                type="button"
                                className="self-start rounded-md border border-red-300 bg-white px-4 py-2 font-semibold disabled:opacity-50 sm:self-auto"
                                disabled={isFetching}
                                onClick={() => void paymentStore.init(requestParams, scope)}
                            >
                                {isFetching ? "Retrying..." : "Retry"}
                            </button>
                        </div>
                    ) : null}

                    {paymentStore.payments.length === 0 ? (
                        <EmptyState
                            title="No payments found"
                            description="Try changing the filters or return to the complete payment history."
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
                                {paymentStore.payments.map((payment) => (
                                    <PaymentCatalogCard
                                        key={payment.id}
                                        payment={payment}
                                        scope={scope}
                                        detailsBasePath={paymentsBasePath}
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                    {pagination ? (
                        <div className="mt-10">
                            <PaginationControls
                                currentPage={pagination.page}
                                totalPages={pagination.pages}
                                disabled={isFetching || paymentStore.error !== null}
                                onPageChange={changePage}
                            />
                        </div>
                    ) : null}
                </div>
            ) : null}
        </section>
    );
});

export default PaymentsCatalog;
