'use client'

import { useEffect, useMemo, useRef, useState } from "react";
import { AnimatePresence, motion, useReducedMotion } from "framer-motion";
import { usePathname, useSearchParams } from "next/navigation";
import { useForm } from "react-hook-form";
import { FunnelIcon } from "@heroicons/react/24/outline";
import { SelectedPage } from "@/shared/types";
import Trainers from "@/scenes/ourTrainers/Trainers";
import { useNavigation } from "@/context/navigation-context";
import HText from "@/shared/HText";
import LoadingState from "@/shared/ui/LoadingState";
import EmptyState from "@/shared/ui/EmptyState";
import ErrorState from "@/shared/ui/ErrorState";
import PaginationControls from "@/shared/ui/PaginationControls";
import type TrainingTypeData from "@/types/training-type.type";
import {
    DEFAULT_TRAINERS_SORT,
    getTrainers,
    getTrainersRequestKey,
    parseTrainersListParams,
    type TrainersListParams,
    type TrainersListResponse,
} from "@/api/public/trainers.api";
import { getErrorMessage } from "@/lib/getErrorMessage";

const SORT_OPTIONS = [
    { value: "lastName:ASC", label: "Last name (A-Z)" },
    { value: "lastName:DESC", label: "Last name (Z-A)" },
    { value: "firstName:ASC", label: "First name (A-Z)" },
    { value: "firstName:DESC", label: "First name (Z-A)" },
    { value: "pricePerHour:ASC", label: "Price (low to high)" },
    { value: "pricePerHour:DESC", label: "Price (high to low)" },
    { value: "trainingTypeId:ASC", label: "Training type ID (ascending)" },
    { value: "trainingTypeId:DESC", label: "Training type ID (descending)" },
] as const;

const FILTER_QUERY_KEYS = [
    "minPricePerHour",
    "maxPricePerHour",
    "trainingTypeId",
    "sort",
    "limit",
] as const;

const FILTER_PANEL_ID = "trainer-filters-panel";

type Props = {
    trainingTypes: TrainingTypeData[];
};

type TrainerFiltersForm = {
    minPricePerHour: string;
    maxPricePerHour: string;
    trainingTypeId: string;
    sort: string;
    limit: string;
};

type TrainersResponseState = {
    requestToken: string;
    response: TrainersListResponse;
};

type TrainersErrorState = {
    requestToken: string;
    message: string;
};

const toFormValues = (params: TrainersListParams): TrainerFiltersForm => ({
    minPricePerHour: params.minPricePerHour?.toString() ?? "",
    maxPricePerHour: params.maxPricePerHour?.toString() ?? "",
    trainingTypeId: params.trainingTypeId?.toString() ?? "",
    sort: params.sort ?? DEFAULT_TRAINERS_SORT,
    limit: params.limit?.toString() ?? "",
});

const normalizePositiveInteger = (value: string): string => {
    return value === "" ? "" : Number(value).toString();
};

const isAbortError = (error: unknown): boolean => {
    return error instanceof Error && error.name === "AbortError";
};

const OurTrainers = ({ trainingTypes }: Props) => {
    const { setSelectedPage } = useNavigation();
    const shouldReduceMotion = useReducedMotion();
    const pathname = usePathname();
    const searchParams = useSearchParams();
    const searchParamsString = searchParams.toString();
    const requestParams = useMemo(
        () => parseTrainersListParams(new URLSearchParams(searchParamsString)),
        [searchParamsString],
    );
    const requestKey = useMemo(
        () => getTrainersRequestKey(requestParams),
        [requestParams],
    );
    const formValues = useMemo(() => toFormValues(requestParams), [requestParams]);
    const [responseState, setResponseState] = useState<TrainersResponseState | null>(null);
    const [errorState, setErrorState] = useState<TrainersErrorState | null>(null);
    const [isFiltersOpen, setIsFiltersOpen] = useState(false);
    const [retryVersion, setRetryVersion] = useState(0);
    const requestSequence = useRef(0);
    const requestToken = `${requestKey}:${retryVersion}`;

    const {
        register,
        handleSubmit,
        reset,
        getValues,
        formState: { errors },
    } = useForm<TrainerFiltersForm>({
        defaultValues: formValues,
    });

    useEffect(() => {
        reset(formValues);
    }, [formValues, reset]);

    useEffect(() => {
        const controller = new AbortController();
        const requestId = ++requestSequence.current;
        const params = parseTrainersListParams(new URLSearchParams(requestKey));

        void getTrainers(params, { signal: controller.signal })
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
                    message: getErrorMessage(requestError, "Unable to load trainers."),
                });
            });

        return () => {
            controller.abort();
        };
    }, [requestKey, requestToken]);

    const updateUrl = (nextSearchParams: URLSearchParams) => {
        const queryString = nextSearchParams.toString();
        const nextUrl = `${pathname}${queryString ? `?${queryString}` : ""}#ourtrainers`;

        window.history.pushState(null, "", nextUrl);
        window.requestAnimationFrame(() => {
            document.getElementById("ourtrainers")?.scrollIntoView({
                behavior: "smooth",
                block: "start",
            });
        });
    };

    const applyFilters = (values: TrainerFiltersForm) => {
        const nextSearchParams = new URLSearchParams(searchParamsString);
        const minPrice = normalizePositiveInteger(values.minPricePerHour);
        const maxPrice = normalizePositiveInteger(values.maxPricePerHour);
        const trainingTypeId = normalizePositiveInteger(values.trainingTypeId);
        const limit = normalizePositiveInteger(values.limit);

        const setOrDelete = (key: string, value: string) => {
            if (value === "") {
                nextSearchParams.delete(key);
            } else {
                nextSearchParams.set(key, value);
            }
        };

        setOrDelete("minPricePerHour", minPrice);
        setOrDelete("maxPricePerHour", maxPrice);
        setOrDelete("trainingTypeId", trainingTypeId);
        setOrDelete("limit", limit);

        if (values.sort === DEFAULT_TRAINERS_SORT) {
            nextSearchParams.delete("sort");
        } else {
            nextSearchParams.set("sort", values.sort);
        }

        nextSearchParams.delete("page");
        updateUrl(nextSearchParams);
        setIsFiltersOpen(false);
    };

    const clearFilters = () => {
        const nextSearchParams = new URLSearchParams(searchParamsString);

        FILTER_QUERY_KEYS.forEach((key) => nextSearchParams.delete(key));
        nextSearchParams.delete("page");
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

    const hasCustomSort = !SORT_OPTIONS.some((option) => option.value === formValues.sort);
    const response = responseState?.response ?? null;
    const error = errorState?.requestToken === requestToken ? errorState.message : null;
    const isFetching = responseState?.requestToken !== requestToken
        && errorState?.requestToken !== requestToken;
    const trainers = response?.data ?? [];
    const pagination = response?.meta.pagination;
    const hasActiveFilters = FILTER_QUERY_KEYS.some((key) => searchParams.has(key));
    const isInitialLoading = response === null && isFetching;

    return (
        <section id="ourtrainers" className="mx-auto min-h-full w-5/6 scroll-mt-24 py-20">
            <motion.div
                initial="hidden"
                whileInView="visible"
                viewport={{ once: true, amount: 0.5 }}
                transition={{ duration: 0.5 }}
                variants={{
                    hidden: { opacity: 0, x: -50 },
                    visible: { opacity: 1, x: 0 },
                }}
            >
                <div className="md:w-3/5">
                    <HText>OUR TRAINERS</HText>
                    <p className="py-5">
                        Shatter your limits with our feral trainer legion: Powerlifting Overlords,
                        HIIT Battle Commanders, Ruthless Bodybuilding Beasts. Elite coaches who`ve
                        crushed world records and forged ironclad physiques in the fires of war.
                        No weaklings. Only scarred veterans who drag you past total failure. Train
                        under gods. Annihilate frailty.
                    </p>
                </div>
            </motion.div>

            <div className="mb-8 flex flex-wrap items-center justify-between gap-4 border-y border-gray-100 py-4">
                <p className="text-sm font-semibold text-gray-500">
                    Refine the trainer catalog
                </p>
                <button
                    type="button"
                    aria-expanded={isFiltersOpen}
                    aria-controls={FILTER_PANEL_ID}
                    className={`flex min-h-11 items-center gap-2 rounded-md border px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-gray-20 ${
                        isFiltersOpen
                            ? "border-primary-500 bg-primary-100 text-gray-500"
                            : "border-gray-100 text-gray-500 hover:border-secondary-500"
                    }`}
                    onClick={() => setIsFiltersOpen((isOpen) => !isOpen)}
                >
                    <FunnelIcon className="h-5 w-5" aria-hidden="true" />
                    <span>Filters &amp; sorting</span>
                    {hasActiveFilters ? (
                        <span className="rounded-full bg-secondary-500 px-2 py-0.5 text-xs font-bold text-gray-500">
                            Active
                        </span>
                    ) : null}
                </button>
            </div>

            <AnimatePresence initial={false}>
                {isFiltersOpen ? (
                    <motion.form
                        id={FILTER_PANEL_ID}
                        className="mb-10 border-b border-gray-100 pb-6"
                        onSubmit={handleSubmit(applyFilters)}
                        noValidate
                        initial={shouldReduceMotion ? { opacity: 1 } : { opacity: 0, y: -8 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={shouldReduceMotion ? { opacity: 1 } : { opacity: 0, y: -8 }}
                        transition={{ duration: shouldReduceMotion ? 0 : 0.18, ease: "easeOut" }}
                    >
                        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            <label className="flex flex-col gap-2 text-sm font-semibold text-gray-500">
                                Minimum hourly price (cents)
                                <input
                                    type="number"
                                    min="1"
                                    step="1"
                                    inputMode="numeric"
                                    className="rounded-md border border-gray-100 bg-gray-20 px-3 py-2 font-normal text-gray-500 transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                    {...register("minPricePerHour", {
                                        validate: (value) => value === ""
                                            || (Number.isInteger(Number(value)) && Number(value) > 0)
                                            || "Enter a positive whole number.",
                                    })}
                                />
                                {errors.minPricePerHour ? (
                                    <span className="font-normal text-primary-500">{errors.minPricePerHour.message}</span>
                                ) : null}
                            </label>

                            <label className="flex flex-col gap-2 text-sm font-semibold text-gray-500">
                                Maximum hourly price (cents)
                                <input
                                    type="number"
                                    min="1"
                                    step="1"
                                    inputMode="numeric"
                                    className="rounded-md border border-gray-100 bg-gray-20 px-3 py-2 font-normal text-gray-500 transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                    {...register("maxPricePerHour", {
                                        validate: {
                                            positiveInteger: (value) => value === ""
                                                || (Number.isInteger(Number(value)) && Number(value) > 0)
                                                || "Enter a positive whole number.",
                                            range: (value) => {
                                                const minimum = getValues("minPricePerHour");

                                                return value === ""
                                                    || minimum === ""
                                                    || Number(value) >= Number(minimum)
                                                    || "Maximum price must be at least the minimum price.";
                                            },
                                        },
                                    })}
                                />
                                {errors.maxPricePerHour ? (
                                    <span className="font-normal text-primary-500">{errors.maxPricePerHour.message}</span>
                                ) : null}
                            </label>

                            <label className="flex flex-col gap-2 text-sm font-semibold text-gray-500">
                                Training type
                                <select
                                    className="rounded-md border border-gray-100 bg-gray-20 px-3 py-2 font-normal text-gray-500 transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                    {...register("trainingTypeId")}
                                >
                                    <option value="">All training types</option>
                                    {trainingTypes.map((trainingType) => (
                                        <option key={trainingType.id} value={trainingType.id}>
                                            {trainingType.name}
                                        </option>
                                    ))}
                                </select>
                            </label>

                            <label className="flex flex-col gap-2 text-sm font-semibold text-gray-500">
                                Sort
                                <select
                                    className="rounded-md border border-gray-100 bg-gray-20 px-3 py-2 font-normal text-gray-500 transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
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

                            <label className="flex flex-col gap-2 text-sm font-semibold text-gray-500">
                                Results per page
                                <input
                                    type="number"
                                    min="1"
                                    max="100"
                                    step="1"
                                    inputMode="numeric"
                                    placeholder="API default"
                                    className="rounded-md border border-gray-100 bg-gray-20 px-3 py-2 font-normal text-gray-500 transition placeholder:text-gray-500/60 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                    {...register("limit", {
                                        validate: (value) => value === ""
                                            || (Number.isInteger(Number(value))
                                                && Number(value) > 0
                                                && Number(value) <= 100)
                                            || "Enter a whole number from 1 to 100.",
                                    })}
                                />
                                {errors.limit ? (
                                    <span className="font-normal text-primary-500">{errors.limit.message}</span>
                                ) : null}
                            </label>
                        </div>

                        <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                            <button
                                type="submit"
                                className="rounded-md bg-secondary-500 px-5 py-2 font-semibold text-gray-500 transition hover:bg-primary-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-gray-20"
                            >
                                Apply filters
                            </button>
                            <button
                                type="button"
                                className="rounded-md border border-gray-100 px-5 py-2 font-semibold text-gray-500 transition hover:border-secondary-500 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-gray-20"
                                onClick={clearFilters}
                            >
                                Clear filters
                            </button>
                        </div>
                    </motion.form>
                ) : null}
            </AnimatePresence>

            <motion.div onViewportEnter={() => setSelectedPage(SelectedPage.OurTrainers)}>
                {isInitialLoading ? (
                    <LoadingState
                        title="Loading trainers..."
                        description="We are fetching the current trainer catalog."
                    />
                ) : null}

                {!isInitialLoading && response === null && error ? (
                    <ErrorState
                        title="Unable to load trainers"
                        message={error}
                        isRetrying={isFetching}
                        onRetry={() => setRetryVersion((version) => version + 1)}
                    />
                ) : null}

                {response !== null ? (
                    <div aria-busy={isFetching}>
                        <div className="mb-5 flex min-h-6 flex-wrap items-center justify-between gap-3">
                            <p className="text-sm text-gray-600">
                                {pagination
                                    ? `${pagination.total} trainer${pagination.total === 1 ? "" : "s"} found`
                                    : null}
                            </p>
                            {isFetching ? (
                                <p role="status" aria-live="polite" className="text-sm font-semibold text-secondary-500">
                                    Refreshing trainers...
                                </p>
                            ) : null}
                        </div>

                        {error ? (
                            <div
                                role="alert"
                                className="mb-6 flex flex-col gap-3 rounded-xl bg-red-50 p-4 text-red-700 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <p>{error}</p>
                                <button
                                    type="button"
                                    className="self-start rounded-md border border-red-300 bg-white px-4 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-50 sm:self-auto"
                                    disabled={isFetching}
                                    onClick={() => setRetryVersion((version) => version + 1)}
                                >
                                    {isFetching ? "Retrying..." : "Retry"}
                                </button>
                            </div>
                        ) : null}

                        {trainers.length === 0 ? (
                            <EmptyState
                                title="No trainers found"
                                description="Try changing the filters or return to the complete catalog."
                                action={hasActiveFilters ? (
                                    <button
                                        type="button"
                                        className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white"
                                        onClick={clearFilters}
                                    >
                                        Clear filters
                                    </button>
                                ) : undefined}
                            />
                        ) : (
                            <div className={isFetching ? "opacity-60 transition-opacity" : "transition-opacity"}>
                                <Trainers trainers={trainers} />
                            </div>
                        )}

                        {pagination ? (
                            <div className="mt-10">
                                <PaginationControls
                                    currentPage={pagination.page}
                                    totalPages={pagination.pages}
                                    disabled={isFetching || error !== null}
                                    onPageChange={changePage}
                                />
                            </div>
                        ) : null}
                    </div>
                ) : null}
            </motion.div>
        </section>
    );
};

export default OurTrainers;
