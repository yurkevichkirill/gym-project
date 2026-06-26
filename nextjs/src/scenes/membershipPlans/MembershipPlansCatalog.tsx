'use client'

import { useEffect, useMemo, useRef, useState } from "react";
import { usePathname, useSearchParams } from "next/navigation";
import { useForm } from "react-hook-form";
import {
    DEFAULT_MEMBERSHIP_PLANS_SORT,
    getMembershipPlansPage,
    getMembershipPlansRequestKey,
    parseMembershipPlansListParams,
    type MembershipPlansListParams,
    type MembershipPlansListResponse,
} from "@/api/public/membership-plans.api";
import { getErrorMessage } from "@/lib/getErrorMessage";
import EmptyState from "@/shared/ui/EmptyState";
import ErrorState from "@/shared/ui/ErrorState";
import LoadingState from "@/shared/ui/LoadingState";
import PaginationControls from "@/shared/ui/PaginationControls";
import PublicMembershipPlanCard from "@/scenes/membershipPlans/PublicMembershipPlanCard";

const SORT_OPTIONS = [
    { value: "price:ASC", label: "Price (low to high)" },
    { value: "price:DESC", label: "Price (high to low)" },
    { value: "durationDays:ASC", label: "Duration (short to long)" },
    { value: "durationDays:DESC", label: "Duration (long to short)" },
    { value: "sessionLimit:ASC", label: "Session limit (low to high)" },
    { value: "sessionLimit:DESC", label: "Session limit (high to low)" },
] as const;

const QUERY_KEYS = [
    "minDurationDays",
    "maxDurationDays",
    "minSessionLimit",
    "maxSessionLimit",
    "minPrice",
    "maxPrice",
    "isUnlimited",
    "sort",
    "page",
    "limit",
] as const;

type MembershipPlansFiltersForm = {
    minDurationDays: string;
    maxDurationDays: string;
    minSessionLimit: string;
    maxSessionLimit: string;
    minPrice: string;
    maxPrice: string;
    isUnlimited: string;
    sort: string;
    limit: string;
};

type ResponseState = {
    requestToken: string;
    response: MembershipPlansListResponse;
};

type ErrorStateValue = {
    requestToken: string;
    message: string;
};

const toFormValues = (
    params: MembershipPlansListParams,
): MembershipPlansFiltersForm => ({
    minDurationDays: params.minDurationDays?.toString() ?? "",
    maxDurationDays: params.maxDurationDays?.toString() ?? "",
    minSessionLimit: params.minSessionLimit?.toString() ?? "",
    maxSessionLimit: params.maxSessionLimit?.toString() ?? "",
    minPrice: params.minPrice?.toString() ?? "",
    maxPrice: params.maxPrice?.toString() ?? "",
    isUnlimited: params.isUnlimited === undefined ? "" : params.isUnlimited.toString(),
    sort: params.sort ?? DEFAULT_MEMBERSHIP_PLANS_SORT,
    limit: params.limit?.toString() ?? "",
});

const normalizeNonNegativeInteger = (value: string): string => {
    return value === "" ? "" : Number(value).toString();
};

const isAbortError = (error: unknown): boolean => {
    return error instanceof Error && error.name === "AbortError";
};

const MembershipPlansCatalog = () => {
    const pathname = usePathname();
    const searchParams = useSearchParams();
    const searchParamsString = searchParams.toString();
    const requestParams = useMemo(
        () => parseMembershipPlansListParams(new URLSearchParams(searchParamsString)),
        [searchParamsString],
    );
    const requestKey = useMemo(
        () => getMembershipPlansRequestKey(requestParams),
        [requestParams],
    );
    const formValues = useMemo(() => toFormValues(requestParams), [requestParams]);
    const [responseState, setResponseState] = useState<ResponseState | null>(null);
    const [errorState, setErrorState] = useState<ErrorStateValue | null>(null);
    const [retryVersion, setRetryVersion] = useState(0);
    const requestSequence = useRef(0);
    const requestToken = `${requestKey}:${retryVersion}`;

    const {
        register,
        handleSubmit,
        reset,
        getValues,
        formState: { errors },
    } = useForm<MembershipPlansFiltersForm>({
        defaultValues: formValues,
    });

    useEffect(() => {
        reset(formValues);
    }, [formValues, reset]);

    useEffect(() => {
        const controller = new AbortController();
        const requestId = ++requestSequence.current;
        const params = parseMembershipPlansListParams(new URLSearchParams(requestKey));

        void getMembershipPlansPage(params, { signal: controller.signal })
            .then((nextResponse) => {
                if (requestId !== requestSequence.current) {
                    return;
                }

                setResponseState({
                    requestToken,
                    response: nextResponse,
                });
            })
            .catch((requestError: unknown) => {
                if (isAbortError(requestError) || requestId !== requestSequence.current) {
                    return;
                }

                setErrorState({
                    requestToken,
                    message: getErrorMessage(requestError, "Unable to load membership plans."),
                });
            });

        return () => {
            controller.abort();
        };
    }, [requestKey, requestToken]);

    const updateUrl = (nextSearchParams: URLSearchParams) => {
        const queryString = nextSearchParams.toString();
        const nextUrl = `${pathname}${queryString ? `?${queryString}` : ""}`;

        window.history.pushState(null, "", nextUrl);
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const applyFilters = (values: MembershipPlansFiltersForm) => {
        const nextSearchParams = new URLSearchParams(searchParamsString);
        const setOrDelete = (key: string, value: string) => {
            if (value === "") {
                nextSearchParams.delete(key);
            } else {
                nextSearchParams.set(key, value);
            }
        };

        setOrDelete("minDurationDays", normalizeNonNegativeInteger(values.minDurationDays));
        setOrDelete("maxDurationDays", normalizeNonNegativeInteger(values.maxDurationDays));
        setOrDelete("minSessionLimit", normalizeNonNegativeInteger(values.minSessionLimit));
        setOrDelete("maxSessionLimit", normalizeNonNegativeInteger(values.maxSessionLimit));
        setOrDelete("minPrice", normalizeNonNegativeInteger(values.minPrice));
        setOrDelete("maxPrice", normalizeNonNegativeInteger(values.maxPrice));
        setOrDelete("isUnlimited", values.isUnlimited);
        setOrDelete("limit", normalizeNonNegativeInteger(values.limit));

        if (values.sort === DEFAULT_MEMBERSHIP_PLANS_SORT) {
            nextSearchParams.delete("sort");
        } else {
            nextSearchParams.set("sort", values.sort);
        }

        nextSearchParams.delete("page");
        updateUrl(nextSearchParams);
    };

    const resetView = () => {
        const nextSearchParams = new URLSearchParams(searchParamsString);

        QUERY_KEYS.forEach((key) => nextSearchParams.delete(key));
        reset(toFormValues({}));
        updateUrl(nextSearchParams);
    };

    const changePage = (page: number) => {
        const nextSearchParams = new URLSearchParams(searchParamsString);

        if (page <= 1) {
            nextSearchParams.delete("page");
        } else {
            nextSearchParams.set("page", page.toString());
        }

        updateUrl(nextSearchParams);
    };

    const validateNonNegativeInteger = (value: string) => value === ""
        || (Number.isInteger(Number(value)) && Number(value) >= 0)
        || "Enter a non-negative whole number.";

    const validateRange = (minimumField: keyof MembershipPlansFiltersForm) => (value: string) => {
        const minimum = getValues(minimumField);

        return value === ""
            || minimum === ""
            || Number(value) >= Number(minimum)
            || "Maximum must be at least the minimum.";
    };

    const response = responseState?.response ?? null;
    const error = errorState?.requestToken === requestToken ? errorState.message : null;
    const isFetching = responseState?.requestToken !== requestToken
        && errorState?.requestToken !== requestToken;
    const membershipPlans = response?.data ?? [];
    const pagination = response?.meta.pagination;
    const isInitialLoading = response === null && isFetching;
    const hasQueryState = QUERY_KEYS.some((key) => searchParams.has(key));
    const hasCustomSort = !SORT_OPTIONS.some((option) => option.value === formValues.sort);

    return (
        <section className="mx-auto w-full max-w-6xl">
            <div className="mb-8 max-w-3xl">
                <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                    Membership catalog
                </p>
                <h1 className="mt-2 text-3xl font-bold sm:text-4xl">Membership plans</h1>
                <p className="mt-4 text-gray-600">
                    Compare limited and unlimited plans. Purchases are available only from an authenticated client account.
                </p>
            </div>

            <form
                className="mb-8 rounded-2xl bg-white p-5 shadow-sm sm:p-6"
                onSubmit={handleSubmit(applyFilters)}
                noValidate
            >
                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <label className="flex flex-col gap-2 text-sm font-semibold">
                        Minimum duration (days)
                        <input type="number" min="0" step="1" inputMode="numeric" className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none" {...register("minDurationDays", { validate: validateNonNegativeInteger })} />
                        {errors.minDurationDays ? <span className="font-normal text-red-600">{errors.minDurationDays.message}</span> : null}
                    </label>

                    <label className="flex flex-col gap-2 text-sm font-semibold">
                        Maximum duration (days)
                        <input type="number" min="0" step="1" inputMode="numeric" className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none" {...register("maxDurationDays", { validate: { integer: validateNonNegativeInteger, range: validateRange("minDurationDays") } })} />
                        {errors.maxDurationDays ? <span className="font-normal text-red-600">{errors.maxDurationDays.message}</span> : null}
                    </label>

                    <label className="flex flex-col gap-2 text-sm font-semibold">
                        Plan type
                        <select className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none" {...register("isUnlimited")}>
                            <option value="">All plans</option>
                            <option value="false">Limited</option>
                            <option value="true">Unlimited</option>
                        </select>
                    </label>

                    <label className="flex flex-col gap-2 text-sm font-semibold">
                        Minimum sessions
                        <input type="number" min="0" step="1" inputMode="numeric" className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none" {...register("minSessionLimit", { validate: validateNonNegativeInteger })} />
                        {errors.minSessionLimit ? <span className="font-normal text-red-600">{errors.minSessionLimit.message}</span> : null}
                    </label>

                    <label className="flex flex-col gap-2 text-sm font-semibold">
                        Maximum sessions
                        <input type="number" min="0" step="1" inputMode="numeric" className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none" {...register("maxSessionLimit", { validate: { integer: validateNonNegativeInteger, range: validateRange("minSessionLimit") } })} />
                        {errors.maxSessionLimit ? <span className="font-normal text-red-600">{errors.maxSessionLimit.message}</span> : null}
                    </label>

                    <label className="flex flex-col gap-2 text-sm font-semibold">
                        Minimum price (cents)
                        <input type="number" min="0" step="1" inputMode="numeric" className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none" {...register("minPrice", { validate: validateNonNegativeInteger })} />
                        {errors.minPrice ? <span className="font-normal text-red-600">{errors.minPrice.message}</span> : null}
                    </label>

                    <label className="flex flex-col gap-2 text-sm font-semibold">
                        Maximum price (cents)
                        <input type="number" min="0" step="1" inputMode="numeric" className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none" {...register("maxPrice", { validate: { integer: validateNonNegativeInteger, range: validateRange("minPrice") } })} />
                        {errors.maxPrice ? <span className="font-normal text-red-600">{errors.maxPrice.message}</span> : null}
                    </label>

                    <label className="flex flex-col gap-2 text-sm font-semibold">
                        Sort
                        <select className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none" {...register("sort")}>
                            {hasCustomSort ? <option value={formValues.sort}>Custom: {formValues.sort}</option> : null}
                            {SORT_OPTIONS.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                        </select>
                    </label>

                    <label className="flex flex-col gap-2 text-sm font-semibold">
                        Results per page
                        <input
                            type="number"
                            min="1"
                            max="100"
                            step="1"
                            inputMode="numeric"
                            placeholder="API default"
                            className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none"
                            {...register("limit", {
                                validate: (value) => value === ""
                                    || (Number.isInteger(Number(value)) && Number(value) > 0 && Number(value) <= 100)
                                    || "Enter a whole number from 1 to 100.",
                            })}
                        />
                        {errors.limit ? <span className="font-normal text-red-600">{errors.limit.message}</span> : null}
                    </label>
                </div>

                <div className="mt-6 flex flex-wrap gap-3">
                    <button type="submit" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white">Apply filters</button>
                    <button type="button" className="rounded-md border border-gray-300 bg-white px-5 py-2 font-semibold transition hover:border-secondary-500" onClick={resetView}>Reset</button>
                </div>
            </form>

            {isInitialLoading ? <LoadingState title="Loading membership plans..." description="We are fetching the current membership catalog." /> : null}

            {!isInitialLoading && response === null && error ? (
                <ErrorState title="Unable to load membership plans" message={error} isRetrying={isFetching} onRetry={() => setRetryVersion((version) => version + 1)} />
            ) : null}

            {response !== null ? (
                <div aria-busy={isFetching}>
                    <div className="mb-5 flex min-h-6 flex-wrap items-center justify-between gap-3">
                        <p className="text-sm text-gray-600">
                            {pagination ? `${pagination.total} membership plan${pagination.total === 1 ? "" : "s"} found` : null}
                        </p>
                        {isFetching ? <p role="status" aria-live="polite" className="text-sm font-semibold text-secondary-500">Refreshing membership plans...</p> : null}
                    </div>

                    {error ? (
                        <div role="alert" className="mb-6 flex flex-col gap-3 rounded-xl bg-red-50 p-4 text-red-700 sm:flex-row sm:items-center sm:justify-between">
                            <p>{error}</p>
                            <button type="button" className="self-start rounded-md border border-red-300 bg-white px-4 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-50 sm:self-auto" disabled={isFetching} onClick={() => setRetryVersion((version) => version + 1)}>
                                {isFetching ? "Retrying..." : "Retry"}
                            </button>
                        </div>
                    ) : null}

                    {membershipPlans.length === 0 ? (
                        <EmptyState
                            title="No membership plans found"
                            description="Try changing the filters or returning to the complete catalog."
                            action={hasQueryState ? <button type="button" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white" onClick={resetView}>Reset view</button> : undefined}
                        />
                    ) : (
                        <div className={isFetching ? "opacity-60 transition-opacity" : "transition-opacity"}>
                            <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                                {membershipPlans.map((membershipPlan) => <PublicMembershipPlanCard key={membershipPlan.id} membershipPlan={membershipPlan} />)}
                            </div>
                        </div>
                    )}

                    {pagination ? (
                        <div className="mt-10">
                            <PaginationControls currentPage={pagination.page} totalPages={pagination.pages} disabled={isFetching || error !== null} onPageChange={changePage} />
                        </div>
                    ) : null}
                </div>
            ) : null}
        </section>
    );
};

export default MembershipPlansCatalog;
