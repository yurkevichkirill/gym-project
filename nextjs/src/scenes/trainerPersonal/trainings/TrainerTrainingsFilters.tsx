'use client';

import { DEFAULT_TRAINER_TRAININGS_SORT } from "@/api/trainer/training.api";
import { BookingStatusEnum } from "@/types/booking/bookings-status.enum";
import { TrainerTrainingsGetParams } from "@/types/trainer/private/trainer-training.type";
import { useEffect } from "react";
import { useForm } from "react-hook-form";

const SORT_OPTIONS = [
    { value: "bookedAt:ASC", label: "Booked at (oldest first)" },
    { value: "bookedAt:DESC", label: "Booked at (newest first)" },
    { value: "date:ASC", label: "Date (earliest first)" },
    { value: "date:DESC", label: "Date (latest first)" },
    { value: "startTime:ASC", label: "Start time (earliest first)" },
    { value: "startTime:DESC", label: "Start time (latest first)" },
    { value: "durationMinutes:ASC", label: "Duration (shortest first)" },
    { value: "durationMinutes:DESC", label: "Duration (longest first)" },
    { value: "clientId:ASC", label: "Client ID (ascending)" },
    { value: "clientId:DESC", label: "Client ID (descending)" },
    { value: "status:ASC", label: "Status (ascending)" },
    { value: "status:DESC", label: "Status (descending)" },
    { value: "isBusy:ASC", label: "Busy state (available first)" },
    { value: "isBusy:DESC", label: "Busy state (busy first)" },
] as const;

const STATUS_OPTIONS = Object.values(BookingStatusEnum);

export interface TrainerTrainingsFiltersFormValues {
    clientId: string;
    status: string;
    date: string;
    startTime: string;
    durationMinutes: string;
    isBusy: string;
    sort: string;
    limit: string;
}

export const toTrainerTrainingsFilterValues = (
    params: TrainerTrainingsGetParams,
): TrainerTrainingsFiltersFormValues => ({
    clientId: params.clientId?.toString() ?? "",
    status: params.status ?? "",
    date: params.date ?? "",
    startTime: params.startTime?.slice(0, 5) ?? "",
    durationMinutes: params.durationMinutes?.toString() ?? "",
    isBusy: params.isBusy === undefined ? "" : params.isBusy.toString(),
    sort: params.sort ?? DEFAULT_TRAINER_TRAININGS_SORT,
    limit: params.limit?.toString() ?? "",
});

const TrainerTrainingsFilters = ({
    values,
    disabled,
    onApply,
    onReset,
}: {
    values: TrainerTrainingsFiltersFormValues;
    disabled: boolean;
    onApply: (values: TrainerTrainingsFiltersFormValues) => void;
    onReset: () => void;
}) => {
    const {
        register,
        handleSubmit,
        reset,
        formState: { errors },
    } = useForm<TrainerTrainingsFiltersFormValues>({
        defaultValues: values,
    });

    useEffect(() => {
        reset(values);
    }, [reset, values]);

    const hasCustomSort = !SORT_OPTIONS.some((option) => option.value === values.sort);

    return (
        <form
            className="mb-8 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6"
            onSubmit={handleSubmit(onApply)}
            noValidate
        >
            <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <label className="flex flex-col gap-2 text-sm font-semibold">
                    Client ID
                    <input
                        type="number"
                        min={1}
                        step={1}
                        inputMode="numeric"
                        className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none"
                        {...register("clientId", {
                            validate: (value) => value === ""
                                || (Number.isInteger(Number(value)) && Number(value) > 0)
                                || "Enter a positive whole number.",
                        })}
                    />
                    {errors.clientId ? (
                        <span className="font-normal text-primary-500" role="alert">
                            {errors.clientId.message}
                        </span>
                    ) : null}
                </label>

                <label className="flex flex-col gap-2 text-sm font-semibold">
                    Status
                    <select
                        className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none"
                        {...register("status")}
                    >
                        <option value="">All statuses</option>
                        {STATUS_OPTIONS.map((status) => (
                            <option key={status} value={status}>
                                {status.replace(/_/g, " ")}
                            </option>
                        ))}
                    </select>
                </label>

                <label className="flex flex-col gap-2 text-sm font-semibold">
                    Date
                    <input
                        type="date"
                        className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none"
                        {...register("date")}
                    />
                </label>

                <label className="flex flex-col gap-2 text-sm font-semibold">
                    Start time
                    <input
                        type="time"
                        step={60}
                        className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none"
                        {...register("startTime")}
                    />
                </label>

                <label className="flex flex-col gap-2 text-sm font-semibold">
                    Duration, minutes
                    <input
                        type="number"
                        min={30}
                        step={30}
                        inputMode="numeric"
                        className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none"
                        {...register("durationMinutes", {
                            validate: (value) => value === ""
                                || (
                                    Number.isInteger(Number(value))
                                    && Number(value) >= 30
                                    && Number(value) % 30 === 0
                                )
                                || "Enter 30 minutes or a larger multiple of 30.",
                        })}
                    />
                    {errors.durationMinutes ? (
                        <span className="font-normal text-primary-500" role="alert">
                            {errors.durationMinutes.message}
                        </span>
                    ) : null}
                </label>

                <label className="flex flex-col gap-2 text-sm font-semibold">
                    Busy state
                    <select
                        className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none"
                        {...register("isBusy")}
                    >
                        <option value="">All</option>
                        <option value="true">Busy</option>
                        <option value="false">Released</option>
                    </select>
                </label>

                <label className="flex flex-col gap-2 text-sm font-semibold">
                    Sort
                    <select
                        className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none"
                        {...register("sort")}
                    >
                        {hasCustomSort ? (
                            <option value={values.sort}>Custom: {values.sort}</option>
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
                    disabled={disabled}
                    className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Apply filters
                </button>
                <button
                    type="button"
                    disabled={disabled}
                    className="rounded-md border border-gray-300 bg-white px-5 py-2 font-semibold transition hover:border-secondary-500 disabled:cursor-not-allowed disabled:opacity-50"
                    onClick={onReset}
                >
                    Reset view
                </button>
            </div>
        </form>
    );
};

export default TrainerTrainingsFilters;
