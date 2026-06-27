'use client'

import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { DEFAULT_BOOKINGS_SORT } from "@/api/client/bookings.api";
import { BookingStatusEnum } from "@/types/booking/bookings-status.enum";
import { getBookingStatusLabel } from "@/scenes/clientPersonal/bookings/booking-display";

const SORT_OPTIONS = [
    { value: "date:ASC", label: "Training date (earliest first)" },
    { value: "date:DESC", label: "Training date (latest first)" },
    { value: "startTime:ASC", label: "Start time (earliest first)" },
    { value: "startTime:DESC", label: "Start time (latest first)" },
    { value: "bookedAt:DESC", label: "Booked most recently" },
    { value: "bookedAt:ASC", label: "Booked least recently" },
    { value: "durationMinutes:ASC", label: "Duration (shortest first)" },
    { value: "durationMinutes:DESC", label: "Duration (longest first)" },
    { value: "status:ASC", label: "Status (A–Z)" },
    { value: "status:DESC", label: "Status (Z–A)" },
    { value: "trainingId:ASC", label: "Training ID (ascending)" },
    { value: "trainingId:DESC", label: "Training ID (descending)" },
] as const;

export type BookingsFiltersForm = {
    trainerId: string;
    status: string;
    date: string;
    startTime: string;
    durationMinutes: string;
    sort: string;
    limit: string;
};

type BookingsFiltersProps = {
    values: BookingsFiltersForm;
    onApply: (values: BookingsFiltersForm) => void;
    onReset: () => void;
};

const inputClassName = "rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none";
const labelClassName = "flex flex-col gap-2 text-sm font-semibold";

const BookingsFilters = ({ values, onApply, onReset }: BookingsFiltersProps) => {
    const {
        register,
        handleSubmit,
        reset,
        formState: { errors },
    } = useForm<BookingsFiltersForm>({ defaultValues: values });

    useEffect(() => {
        reset(values);
    }, [reset, values]);

    const positiveInteger = (value: string) => value === ""
        || (Number.isInteger(Number(value)) && Number(value) > 0)
        || "Enter a positive whole number.";
    const duration = (value: string) => value === ""
        || (Number.isInteger(Number(value)) && Number(value) >= 30 && Number(value) <= 1440 && Number(value) % 30 === 0)
        || "Use a multiple of 30 between 30 and 1440.";
    const limit = (value: string) => value === ""
        || (Number.isInteger(Number(value)) && Number(value) >= 1 && Number(value) <= 100)
        || "Use a value between 1 and 100.";
    const hasCustomSort = !SORT_OPTIONS.some((option) => option.value === values.sort);

    return (
        <form className="mb-8 rounded-2xl bg-white p-5 shadow-sm sm:p-6" onSubmit={handleSubmit(onApply)} noValidate>
            <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <label className={labelClassName}>
                    Trainer ID
                    <input type="number" min="1" step="1" inputMode="numeric" className={inputClassName} {...register("trainerId", { validate: positiveInteger })} />
                    {errors.trainerId ? <span className="font-normal text-red-600">{errors.trainerId.message}</span> : null}
                </label>

                <label className={labelClassName}>
                    Status
                    <select className={inputClassName} {...register("status")}>
                        <option value="">All statuses</option>
                        {Object.values(BookingStatusEnum).map((status) => (
                            <option key={status} value={status}>{getBookingStatusLabel(status)}</option>
                        ))}
                    </select>
                </label>

                <label className={labelClassName}>
                    Training date
                    <input type="date" className={inputClassName} {...register("date")} />
                </label>

                <label className={labelClassName}>
                    Start time
                    <input type="time" step="60" className={inputClassName} {...register("startTime")} />
                </label>

                <label className={labelClassName}>
                    Duration (minutes)
                    <input type="number" min="30" max="1440" step="30" inputMode="numeric" className={inputClassName} {...register("durationMinutes", { validate: duration })} />
                    {errors.durationMinutes ? <span className="font-normal text-red-600">{errors.durationMinutes.message}</span> : null}
                </label>

                <label className={`${labelClassName} sm:col-span-2`}>
                    Sort
                    <select className={inputClassName} {...register("sort")}>
                        {hasCustomSort ? <option value={values.sort}>Custom: {values.sort}</option> : null}
                        {SORT_OPTIONS.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                    </select>
                </label>

                <label className={labelClassName}>
                    Results per page
                    <input type="number" min="1" max="100" step="1" inputMode="numeric" placeholder="API default" className={inputClassName} {...register("limit", { validate: limit })} />
                    {errors.limit ? <span className="font-normal text-red-600">{errors.limit.message}</span> : null}
                </label>
            </div>

            <div className="mt-6 flex flex-wrap gap-3">
                <button type="submit" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white">Apply filters</button>
                <button type="button" className="rounded-md border border-gray-300 bg-white px-5 py-2 font-semibold transition hover:border-secondary-500" onClick={onReset}>Reset</button>
            </div>
        </form>
    );
};

export const toBookingsFilterValues = (params: {
    trainerId?: number;
    status?: BookingStatusEnum;
    date?: string;
    startTime?: string;
    durationMinutes?: number;
    sort?: string;
    limit?: number;
}): BookingsFiltersForm => ({
    trainerId: params.trainerId?.toString() ?? "",
    status: params.status ?? "",
    date: params.date ?? "",
    startTime: params.startTime?.slice(0, 5) ?? "",
    durationMinutes: params.durationMinutes?.toString() ?? "",
    sort: params.sort ?? DEFAULT_BOOKINGS_SORT,
    limit: params.limit?.toString() ?? "",
});

export default BookingsFilters;
