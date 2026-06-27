'use client'

import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { DEFAULT_PAYMENTS_SORT } from "@/api/client/payments.api";
import { PaymentStatusEnum } from "@/types/payment/payment-status.enum";
import { getPaymentStatusLabel } from "@/scenes/clientPersonal/payment/payment-display";

const SORT_OPTIONS = [
    { value: "createdAt:DESC", label: "Created date (latest first)" },
    { value: "createdAt:ASC", label: "Created date (earliest first)" },
    { value: "paidAt:DESC", label: "Paid date (latest first)" },
    { value: "paidAt:ASC", label: "Paid date (earliest first)" },
    { value: "amount:DESC", label: "Amount (highest first)" },
    { value: "amount:ASC", label: "Amount (lowest first)" },
    { value: "category:ASC", label: "Category (A–Z)" },
    { value: "category:DESC", label: "Category (Z–A)" },
    { value: "status:ASC", label: "Status (A–Z)" },
    { value: "status:DESC", label: "Status (Z–A)" },
    { value: "isRefund:DESC", label: "Refunds first" },
    { value: "isRefund:ASC", label: "Payments first" },
] as const;

export type PaymentsFiltersForm = {
    trainerId: string;
    minAmount: string;
    maxAmount: string;
    isRefund: string;
    status: string;
    minCreatedAt: string;
    maxCreatedAt: string;
    sort: string;
    limit: string;
};

type PaymentsFiltersProps = {
    values: PaymentsFiltersForm;
    onApply: (values: PaymentsFiltersForm) => void;
    onReset: () => void;
};

const inputClassName = "rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none";
const labelClassName = "flex flex-col gap-2 text-sm font-semibold";

const PaymentsFilters = ({ values, onApply, onReset }: PaymentsFiltersProps) => {
    const {
        register,
        handleSubmit,
        reset,
        watch,
        formState: { errors },
    } = useForm<PaymentsFiltersForm>({ defaultValues: values });

    useEffect(() => {
        reset(values);
    }, [reset, values]);

    const positiveInteger = (value: string) => value === ""
        || (Number.isInteger(Number(value)) && Number(value) > 0)
        || "Enter a positive whole number.";
    const limit = (value: string) => value === ""
        || (Number.isInteger(Number(value)) && Number(value) >= 1 && Number(value) <= 100)
        || "Use a value between 1 and 100.";
    const minAmount = watch("minAmount");
    const minCreatedAt = watch("minCreatedAt");
    const hasCustomSort = !SORT_OPTIONS.some((option) => option.value === values.sort);

    return (
        <form
            className="mb-8 rounded-2xl bg-white p-5 shadow-sm sm:p-6"
            onSubmit={handleSubmit(onApply)}
            noValidate
        >
            <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <label className={labelClassName}>
                    Trainer ID
                    <input
                        type="number"
                        min="1"
                        step="1"
                        inputMode="numeric"
                        className={inputClassName}
                        {...register("trainerId", { validate: positiveInteger })}
                    />
                    {errors.trainerId ? (
                        <span className="font-normal text-red-600">{errors.trainerId.message}</span>
                    ) : null}
                </label>

                <label className={labelClassName}>
                    Minimum amount (minor units)
                    <input
                        type="number"
                        min="1"
                        step="1"
                        inputMode="numeric"
                        className={inputClassName}
                        {...register("minAmount", { validate: positiveInteger })}
                    />
                    {errors.minAmount ? (
                        <span className="font-normal text-red-600">{errors.minAmount.message}</span>
                    ) : null}
                </label>

                <label className={labelClassName}>
                    Maximum amount (minor units)
                    <input
                        type="number"
                        min="1"
                        step="1"
                        inputMode="numeric"
                        className={inputClassName}
                        {...register("maxAmount", {
                            validate: (value) => {
                                const positiveResult = positiveInteger(value);

                                if (positiveResult !== true) {
                                    return positiveResult;
                                }

                                return value === ""
                                    || minAmount === ""
                                    || Number(value) >= Number(minAmount)
                                    || "Maximum amount must be greater than or equal to the minimum.";
                            },
                        })}
                    />
                    {errors.maxAmount ? (
                        <span className="font-normal text-red-600">{errors.maxAmount.message}</span>
                    ) : null}
                </label>

                <label className={labelClassName}>
                    Refund
                    <select className={inputClassName} {...register("isRefund")}>
                        <option value="">All payments</option>
                        <option value="false">Original payments</option>
                        <option value="true">Refund payments</option>
                    </select>
                </label>

                <label className={labelClassName}>
                    Status
                    <select className={inputClassName} {...register("status")}>
                        <option value="">All statuses</option>
                        {Object.values(PaymentStatusEnum).map((status) => (
                            <option key={status} value={status}>{getPaymentStatusLabel(status)}</option>
                        ))}
                    </select>
                </label>

                <label className={labelClassName}>
                    Created from
                    <input
                        type="date"
                        className={inputClassName}
                        {...register("minCreatedAt")}
                    />
                </label>

                <label className={labelClassName}>
                    Created to
                    <input
                        type="date"
                        className={inputClassName}
                        {...register("maxCreatedAt", {
                            validate: (value) => value === ""
                                || minCreatedAt === ""
                                || value >= minCreatedAt
                                || "End date must not be before the start date.",
                        })}
                    />
                    {errors.maxCreatedAt ? (
                        <span className="font-normal text-red-600">{errors.maxCreatedAt.message}</span>
                    ) : null}
                </label>

                <label className={labelClassName}>
                    Sort
                    <select className={inputClassName} {...register("sort")}>
                        {hasCustomSort ? <option value={values.sort}>Custom: {values.sort}</option> : null}
                        {SORT_OPTIONS.map((option) => (
                            <option key={option.value} value={option.value}>{option.label}</option>
                        ))}
                    </select>
                </label>

                <label className={labelClassName}>
                    Results per page
                    <input
                        type="number"
                        min="1"
                        max="100"
                        step="1"
                        inputMode="numeric"
                        placeholder="API default"
                        className={inputClassName}
                        {...register("limit", { validate: limit })}
                    />
                    {errors.limit ? (
                        <span className="font-normal text-red-600">{errors.limit.message}</span>
                    ) : null}
                </label>
            </div>

            <div className="mt-6 flex flex-wrap gap-3">
                <button
                    type="submit"
                    className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white"
                >
                    Apply filters
                </button>
                <button
                    type="button"
                    className="rounded-md border border-gray-300 bg-white px-5 py-2 font-semibold transition hover:border-secondary-500"
                    onClick={onReset}
                >
                    Reset
                </button>
            </div>
        </form>
    );
};

export const toPaymentsFilterValues = (params: {
    trainerId?: number;
    minAmount?: number;
    maxAmount?: number;
    isRefund?: boolean;
    status?: PaymentStatusEnum;
    minCreatedAt?: string;
    maxCreatedAt?: string;
    sort?: string;
    limit?: number;
}): PaymentsFiltersForm => ({
    trainerId: params.trainerId?.toString() ?? "",
    minAmount: params.minAmount?.toString() ?? "",
    maxAmount: params.maxAmount?.toString() ?? "",
    isRefund: params.isRefund === undefined ? "" : params.isRefund.toString(),
    status: params.status ?? "",
    minCreatedAt: params.minCreatedAt ?? "",
    maxCreatedAt: params.maxCreatedAt ?? "",
    sort: params.sort ?? DEFAULT_PAYMENTS_SORT,
    limit: params.limit?.toString() ?? "",
});

export default PaymentsFilters;
