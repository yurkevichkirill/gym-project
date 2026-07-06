'use client';

import {
    DEFAULT_TRAINER_WORKTIMES_SORT,
    getTrainerWorktimesRequestKey,
    parseTrainerWorktimesListParams,
} from "@/api/trainer/worktime.api";
import TrainerWorktimeCard from "@/scenes/trainerPersonal/worktime/TrainerWorktimeCard";
import TrainerWorktimeCreateForm from "@/scenes/trainerPersonal/worktime/TrainerWorktimeCreateForm";
import Section, {
    errorStateClassName,
    primaryActionClassName,
    secondaryActionClassName,
} from "@/shared/Section";
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

const inputClassName = "rounded-md border border-gray-100 bg-gray-20 px-3 py-2 font-normal text-gray-500 transition placeholder:text-gray-500/60 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:opacity-60";
const fieldClassName = "flex flex-col gap-2 text-sm font-semibold text-gray-500";

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
        <Section
            title="Worktimes"
            description="Create and manage only your own worktimes. Filtering, sorting and pagination are performed by the server."
        >
            <div>
                <TrainerWorktimeCreateForm />
            </div>

            <form
                className="mt-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6"
                onSubmit={handleSubmit(applyFilters)}
                noValidate
            >
                <div className="grid gap-5 md:grid-cols-3">
                    <label className={fieldClassName}>
                        Exact date
                        <input
                            type="date"
                            className={inputClassName}
                            {...register("date")}
                        />
                        <span className="font-normal text-gray-500">
                            YYYY-MM-DD in the backend timezone
                        </span>
                    </label>

                    <label className={fieldClassName}>
                        Sort
                        <select
                            className={inputClassName}
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

                    <label className={fieldClassName}>
                        Results per page
                        <input
                            type="number"
                            min={1}
                            max={100}
                            step={1}
                            inputMode="numeric"
                            placeholder="API default"
                            className={inputClassName}
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
                        className={primaryActionClassName}
                    >
                        Apply filters
                    </button>
                    <button
                        type="button"
                        disabled={trainerWorktimeStore.isMutating}
                        className={secondaryActionClassName}
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
                                className={`mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between ${errorStateClassName}`}
                            >
                                <p>{trainerWorktimeStore.error}</p>
                                <button
                                    type="button"
                                    disabled={isFetching}
                                    className={`self-start sm:self-auto ${secondaryActionClassName}`}
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
                                        className={primaryActionClassName}
                                        onClick={resetView}
                                    >
                                        Reset view
                                    </button>
                                ) : undefined}
                            />
                        ) : (
                            <div className={isFetching ? "opacity-60 transition-opacity" : "transition-opacity"}>
                                <div className="grid gap-5 lg:grid-cols-2">
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
        </Section>
    );
});

export default TrainerWorktimes;
