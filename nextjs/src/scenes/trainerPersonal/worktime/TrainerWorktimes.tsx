'use client';

import {
    DEFAULT_TRAINER_WORKTIMES_SORT,
    getTrainerWorktimesRequestKey,
    parseTrainerWorktimesListParams,
} from "@/api/trainer/worktime.api";
import TrainerWorktimeCard from "@/scenes/trainerPersonal/worktime/TrainerWorktimeCard";
import TrainerWorktimeCreateForm from "@/scenes/trainerPersonal/worktime/TrainerWorktimeCreateForm";
import EmptyState from "@/shared/ui/EmptyState";
import ErrorState from "@/shared/ui/ErrorState";
import LoadingState from "@/shared/ui/LoadingState";
import PaginationControls from "@/shared/ui/PaginationControls";
import { useStore } from "@/store/StoreProvider";
import { TrainerWorktimesGetParams } from "@/types/trainer/private/trainer-worktime.type";
import { observer } from "mobx-react-lite";
import { usePathname, useSearchParams } from "next/navigation";
import { useEffect, useMemo } from "react";
import { useForm } from "react-hook-form";

const SORT_OPTIONS = [
    { value: "date:ASC", label: "Date (earliest first)" },
    { value: "date:DESC", label: "Date (latest first)" },
    { value: "startTime:ASC", label: "Start time (earliest first)" },
    { value: "startTime:DESC", label: "Start time (latest first)" },
    { value: "endTime:ASC", label: "End time (earliest first)" },
    { value: "endTime:DESC", label: "End time (latest first)" },
] as const;

const QUERY_KEYS = ["date", "sort", "page", "limit"] as const;

type WorktimeFiltersForm = {
    date: string;
    sort: string;
    limit: string;
};

const toFormValues = (
    params: TrainerWorktimesGetParams,
): WorktimeFiltersForm => ({
    date: params.date ?? "",
    sort: params.sort ?? DEFAULT_TRAINER_WORKTIMES_SORT,
    limit: params.limit?.toString() ?? "",
});

const normalizePositiveInteger = (value: string): string => {
    return value === "" ? "" : Number(value).toString();
};

const TrainerWorktimes = observer(() => {
    const { trainerWorktimeStore } = useStore();
    const pathname = usePathname();
    const searchParams = useSearchParams();
    const searchParamsString = searchParams.toString();
    const requestParams = useMemo(
        () => parseTrainerWorktimesListParams(new URLSearchParams(searchParamsString)),
        [searchParamsString],
    );
    const requestKey = useMemo(
        () => getTrainerWorktimesRequestKey(requestParams),
        [requestParams],
    );
    const formValues = useMemo(() => toFormValues(requestParams), [requestParams]);
    const {
        register,
        handleSubmit,
        reset,
        formState: { errors },
    } = useForm<WorktimeFiltersForm>({
        defaultValues: formValues,
    });

    useEffect(() => {
        reset(formValues);
    }, [formValues, reset]);

    useEffect(() => {
        const params = parseTrainerWorktimesListParams(new URLSearchParams(requestKey));

        void trainerWorktimeStore.init(params);
    }, [requestKey, trainerWorktimeStore]);

    const updateUrl = (nextSearchParams: URLSearchParams): void => {
        const queryString = nextSearchParams.toString();
        const nextUrl = `${pathname}${queryString ? `?${queryString}` : ""}`;

        window.history.pushState(null, "", nextUrl);
    };

    const applyFilters = (values: WorktimeFiltersForm): void => {
        const nextSearchParams = new URLSearchParams(searchParamsString);

        if (values.date === "") {
            nextSearchParams.delete("date");
        } else {
            nextSearchParams.set("date", values.date);
        }

        if (values.sort === DEFAULT_TRAINER_WORKTIMES_SORT) {
            nextSearchParams.delete("sort");
        } else {
            nextSearchParams.set("sort", values.sort);
        }

        const normalizedLimit = normalizePositiveInteger(values.limit);
        if (normalizedLimit === "") {
            nextSearchParams.delete("limit");
        } else {
            nextSearchParams.set("limit", normalizedLimit);
        }

        nextSearchParams.delete("page");
        updateUrl(nextSearchParams);
    };

    const resetView = (): void => {
        const nextSearchParams = new URLSearchParams(searchParamsString);

        QUERY_KEYS.forEach((key) => nextSearchParams.delete(key));
        reset(toFormValues({}));
        updateUrl(nextSearchParams);
    };

    const changePage = (page: number): void => {
        const nextSearchParams = new URLSearchParams(searchParamsString);

        if (page <= 1) {
            nextSearchParams.delete("page");
        } else {
            nextSearchParams.set("page", page.toString());
        }

        updateUrl(nextSearchParams);
    };

    const hasResponse = trainerWorktimeStore.loadedRequestKey !== null;
    const isInitialLoading = !hasResponse && trainerWorktimeStore.isLoading;
    const isFetching = trainerWorktimeStore.isLoading || trainerWorktimeStore.isRefreshing;
    const hasQueryState = QUERY_KEYS.some((key) => searchParams.has(key));
    const hasCustomSort = !SORT_OPTIONS.some((option) => option.value === formValues.sort);
    const pagination = trainerWorktimeStore.pagination;

    return (
        <section className="rounded-2xl bg-gray-50 p-5 sm:p-8">
            <div className="max-w-3xl">
                <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                    Trainer-owned schedule
                </p>
                <h2 className="mt-2 text-3xl font-bold">Worktimes</h2>
                <p className="mt-3 text-gray-600">
                    Create and manage only your own worktimes. Filtering, sorting and pagination are performed by the server.
                </p>
            </div>

            <div className="mt-8">
                <TrainerWorktimeCreateForm />
            </div>

            <form
                className="mt-8 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6"
                onSubmit={handleSubmit(applyFilters)}
                noValidate
            >
                <div className="grid gap-5 md:grid-cols-3">
                    <label className="flex flex-col gap-2 text-sm font-semibold">
                        Exact date
                        <input
                            type="date"
                            className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none"
                            {...register("date")}
                        />
                        <span className="font-normal text-gray-500">
                            YYYY-MM-DD in the backend timezone
                        </span>
                    </label>

                    <label className="flex flex-col gap-2 text-sm font-semibold">
                        Sort
                        <select
                            className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none"
                            {...register("sort")}
                        >
                            {hasCustomSort ? (
                                <option value={formValues.sort}>Custom: {formValues.sort}</option>
                            ) : null}
                            {SORT_OPTIONS.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="flex flex-col gap-2 text-sm font-semibold">
                        Results per page
                        <input
                            type="number"
                            min={1}
                            max={100}
                            step={1}
                            inputMode="numeric"
                            placeholder="API default"
                            className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none"
                            {...register("limit", {
                                validate: (value) => value === ""
                                    || (
                                        Number.isInteger(Number(value))
                                        && Number(value) > 0
                                        && Number(value) <= 100
                                    )
                                    || "Enter a whole number from 1 to 100.",
                            })}
                        />
                        {errors.limit ? (
                            <span className="font-normal text-primary-500" role="alert">
                                {errors.limit.message}
                            </span>
                        ) : null}
                    </label>
                </div>

                <div className="mt-6 flex flex-wrap gap-3">
                    <button
                        type="submit"
                        disabled={trainerWorktimeStore.isMutating}
                        className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Apply filters
                    </button>
                    <button
                        type="button"
                        disabled={trainerWorktimeStore.isMutating}
                        className="rounded-md border border-gray-300 bg-white px-5 py-2 font-semibold transition hover:border-secondary-500 disabled:cursor-not-allowed disabled:opacity-50"
                        onClick={resetView}
                    >
                        Reset view
                    </button>
                </div>
            </form>

            <div className="mt-8">
                {isInitialLoading ? (
                    <LoadingState
                        title="Loading your worktimes..."
                        description="We are fetching the trainer-owned schedule from the server."
                    />
                ) : null}

                {!isInitialLoading && !hasResponse && trainerWorktimeStore.error ? (
                    <ErrorState
                        title="Unable to load trainer worktimes"
                        message={trainerWorktimeStore.error}
                        isRetrying={isFetching}
                        onRetry={() => void trainerWorktimeStore.init(requestParams)}
                    />
                ) : null}

                {hasResponse ? (
                    <div aria-busy={isFetching}>
                        <div className="mb-5 flex min-h-6 flex-wrap items-center justify-between gap-3">
                            <p className="text-sm text-gray-600">
                                {pagination
                                    ? `${pagination.total} worktime${pagination.total === 1 ? "" : "s"} found`
                                    : null}
                            </p>
                            {isFetching ? (
                                <p
                                    role="status"
                                    aria-live="polite"
                                    className="text-sm font-semibold text-secondary-500"
                                >
                                    Refreshing worktimes...
                                </p>
                            ) : null}
                        </div>

                        {trainerWorktimeStore.error ? (
                            <div
                                role="alert"
                                className="mb-6 flex flex-col gap-3 rounded-xl bg-red-50 p-4 text-red-700 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <p>{trainerWorktimeStore.error}</p>
                                <button
                                    type="button"
                                    disabled={isFetching}
                                    className="self-start rounded-md border border-red-300 bg-white px-4 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-50 sm:self-auto"
                                    onClick={() => void trainerWorktimeStore.init(requestParams)}
                                >
                                    {isFetching ? "Retrying..." : "Retry"}
                                </button>
                            </div>
                        ) : null}

                        {trainerWorktimeStore.worktimes.length === 0 ? (
                            <EmptyState
                                title="No trainer worktimes found"
                                description="Create a worktime or change the date and pagination filters."
                                action={hasQueryState ? (
                                    <button
                                        type="button"
                                        className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white"
                                        onClick={resetView}
                                    >
                                        Reset view
                                    </button>
                                ) : undefined}
                            />
                        ) : (
                            <div className={isFetching ? "opacity-60 transition-opacity" : "transition-opacity"}>
                                <div className="grid gap-5 xl:grid-cols-2">
                                    {trainerWorktimeStore.worktimes.map((worktime) => (
                                        <TrainerWorktimeCard
                                            key={worktime.id}
                                            worktime={worktime}
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
                                    disabled={isFetching || trainerWorktimeStore.error !== null}
                                    onPageChange={changePage}
                                />
                            </div>
                        ) : null}
                    </div>
                ) : null}
            </div>
        </section>
    );
});

export default TrainerWorktimes;
