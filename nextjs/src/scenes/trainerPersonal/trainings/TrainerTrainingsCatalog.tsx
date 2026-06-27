'use client';

import {
    DEFAULT_TRAINER_TRAININGS_SORT,
    getTrainerTrainingsRequestKey,
    parseTrainerTrainingsListParams,
    TRAINER_TRAINING_QUERY_KEYS,
} from "@/api/trainer/training.api";
import TrainerTrainingCard from "@/scenes/trainerPersonal/trainings/TrainerTrainingCard";
import TrainerTrainingsFilters, {
    toTrainerTrainingsFilterValues,
    TrainerTrainingsFiltersFormValues,
} from "@/scenes/trainerPersonal/trainings/TrainerTrainingsFilters";
import EmptyState from "@/shared/ui/EmptyState";
import ErrorState from "@/shared/ui/ErrorState";
import LoadingState from "@/shared/ui/LoadingState";
import PaginationControls from "@/shared/ui/PaginationControls";
import { useStore } from "@/store/StoreProvider";
import { observer } from "mobx-react-lite";
import Link from "next/link";
import { usePathname, useSearchParams } from "next/navigation";
import { useEffect, useMemo } from "react";

const normalizeInteger = (value: string): string => {
    return value === "" ? "" : Number(value).toString();
};

const TrainerTrainingsCatalog = observer(() => {
    const { trainerTrainingStore } = useStore();
    const pathname = usePathname();
    const searchParams = useSearchParams();
    const searchParamsString = searchParams.toString();
    const requestParams = useMemo(
        () => parseTrainerTrainingsListParams(new URLSearchParams(searchParamsString)),
        [searchParamsString],
    );
    const requestKey = useMemo(
        () => getTrainerTrainingsRequestKey(requestParams),
        [requestParams],
    );
    const formValues = useMemo(
        () => toTrainerTrainingsFilterValues(requestParams),
        [requestParams],
    );

    useEffect(() => {
        const params = parseTrainerTrainingsListParams(new URLSearchParams(requestKey));

        void trainerTrainingStore.init(params);
    }, [requestKey, trainerTrainingStore]);

    const updateUrl = (nextSearchParams: URLSearchParams): void => {
        const queryString = nextSearchParams.toString();

        window.history.pushState(
            null,
            "",
            `${pathname}${queryString ? `?${queryString}` : ""}`,
        );
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const applyFilters = (
        values: TrainerTrainingsFiltersFormValues,
    ): void => {
        const next = new URLSearchParams(searchParamsString);
        const setOrDelete = (key: string, value: string): void => {
            if (value === "") {
                next.delete(key);
            } else {
                next.set(key, value);
            }
        };

        setOrDelete("clientId", normalizeInteger(values.clientId));
        setOrDelete("status", values.status);
        setOrDelete("date", values.date);
        setOrDelete(
            "startTime",
            values.startTime === "" ? "" : `${values.startTime}:00`,
        );
        setOrDelete(
            "durationMinutes",
            normalizeInteger(values.durationMinutes),
        );
        setOrDelete("isBusy", values.isBusy);
        setOrDelete("limit", normalizeInteger(values.limit));
        setOrDelete(
            "sort",
            values.sort === DEFAULT_TRAINER_TRAININGS_SORT ? "" : values.sort,
        );
        next.delete("page");
        updateUrl(next);
    };

    const resetView = (): void => {
        const next = new URLSearchParams(searchParamsString);

        TRAINER_TRAINING_QUERY_KEYS.forEach((key) => next.delete(key));
        updateUrl(next);
    };

    const changePage = (page: number): void => {
        const next = new URLSearchParams(searchParamsString);

        if (page <= 1) {
            next.delete("page");
        } else {
            next.set("page", page.toString());
        }

        updateUrl(next);
    };

    const hasResponse = trainerTrainingStore.loadedRequestKey !== null;
    const isFetching = trainerTrainingStore.isLoading
        || trainerTrainingStore.isRefreshing;
    const isInitialLoading = !hasResponse && isFetching;
    const pagination = trainerTrainingStore.pagination;
    const hasQueryState = TRAINER_TRAINING_QUERY_KEYS.some(
        (key) => searchParams.has(key),
    );

    return (
        <section className="mx-auto w-full max-w-7xl">
            <div className="mb-8 flex flex-wrap items-end justify-between gap-5">
                <div className="max-w-3xl">
                    <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                        Trainer cabinet
                    </p>
                    <h1 className="mt-2 text-3xl font-bold sm:text-4xl">
                        My trainings
                    </h1>
                    <p className="mt-4 text-gray-600">
                        Review trainer-owned trainings using all filters, sorting and pagination supported by the API. Mutations are followed by fresh list and detail requests.
                    </p>
                </div>
                <Link
                    href="/me"
                    className="rounded-md border border-gray-300 bg-white px-4 py-2 font-semibold transition hover:border-secondary-500"
                >
                    Back to cabinet
                </Link>
            </div>

            <TrainerTrainingsFilters
                values={formValues}
                disabled={trainerTrainingStore.isMutating}
                onApply={applyFilters}
                onReset={resetView}
            />

            {isInitialLoading ? (
                <LoadingState
                    title="Loading trainer trainings..."
                    description="We are fetching the current trainer-owned training list."
                />
            ) : null}

            {!isInitialLoading
                && !hasResponse
                && trainerTrainingStore.errorStatus === 403 ? (
                    <EmptyState
                        title="Access denied"
                        description="Your account is not allowed to view trainer trainings."
                        action={(
                            <Link
                                href="/me"
                                className="rounded-md bg-secondary-500 px-5 py-2 font-semibold"
                            >
                                Back to cabinet
                            </Link>
                        )}
                    />
                ) : null}

            {!isInitialLoading
                && !hasResponse
                && trainerTrainingStore.error
                && trainerTrainingStore.errorStatus !== 403 ? (
                    <ErrorState
                        title="Unable to load trainer trainings"
                        message={trainerTrainingStore.error}
                        isRetrying={isFetching}
                        onRetry={() => void trainerTrainingStore.init(requestParams)}
                    />
                ) : null}

            {hasResponse ? (
                <div aria-busy={isFetching}>
                    <div className="mb-5 flex min-h-6 flex-wrap items-center justify-between gap-3">
                        <p className="text-sm text-gray-600">
                            {pagination
                                ? `${pagination.total} training${pagination.total === 1 ? "" : "s"} found`
                                : null}
                        </p>
                        {isFetching ? (
                            <p
                                role="status"
                                aria-live="polite"
                                className="text-sm font-semibold text-secondary-500"
                            >
                                Refreshing trainings...
                            </p>
                        ) : null}
                    </div>

                    {trainerTrainingStore.error ? (
                        <div
                            role="alert"
                            className="mb-6 flex flex-col gap-3 rounded-xl bg-red-50 p-4 text-red-700 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <p>
                                {trainerTrainingStore.errorStatus === 403
                                    ? "Access to the requested trainer training list was denied."
                                    : trainerTrainingStore.error}
                            </p>
                            <button
                                type="button"
                                className="self-start rounded-md border border-red-300 bg-white px-4 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-50 sm:self-auto"
                                disabled={isFetching}
                                onClick={() => void trainerTrainingStore.init(requestParams)}
                            >
                                {isFetching ? "Retrying..." : "Retry"}
                            </button>
                        </div>
                    ) : null}

                    {trainerTrainingStore.trainings.length === 0 ? (
                        <EmptyState
                            title="No trainer trainings found"
                            description="Try changing the filters or return to the complete training history."
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
                                {trainerTrainingStore.trainings.map((training) => (
                                    <TrainerTrainingCard
                                        key={training.id}
                                        training={training}
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
                                disabled={isFetching || trainerTrainingStore.error !== null}
                                onPageChange={changePage}
                            />
                        </div>
                    ) : null}
                </div>
            ) : null}
        </section>
    );
});

export default TrainerTrainingsCatalog;
