'use client'

import { useEffect } from "react";
import { useForm } from "react-hook-form";
import {
    DEFAULT_ADMIN_CLIENTS_SORT,
} from "@/api/admin/clients.api";
import { AdminClientsGetQueryParams } from "@/types/admin/admin-client.type";

export type AdminClientsFiltersForm = {
    minAge: string;
    maxAge: string;
    minBalance: string;
    maxBalance: string;
    isDeleted: "" | "true" | "false";
    sort: string;
    limit: string;
};

const SORT_OPTIONS = [
    { value: DEFAULT_ADMIN_CLIENTS_SORT, label: "Age ascending" },
    { value: "age:DESC", label: "Age descending" },
    { value: "firstName:ASC", label: "First name A-Z" },
    { value: "lastName:ASC", label: "Last name A-Z" },
    { value: "balance:DESC", label: "Balance high first" },
    { value: "createdAt:DESC", label: "Newest first" },
    { value: "updatedAt:DESC", label: "Recently updated" },
    { value: "deletedAt:DESC", label: "Recently deleted" },
] as const;

export const toAdminClientsFilterValues = (params: AdminClientsGetQueryParams): AdminClientsFiltersForm => ({
    minAge: params.minAge?.toString() ?? "",
    maxAge: params.maxAge?.toString() ?? "",
    minBalance: params.minBalance?.toString() ?? "",
    maxBalance: params.maxBalance?.toString() ?? "",
    isDeleted: params.isDeleted === undefined ? "" : params.isDeleted ? "true" : "false",
    sort: params.sort ?? DEFAULT_ADMIN_CLIENTS_SORT,
    limit: params.limit?.toString() ?? "20",
});

type AdminClientsFiltersProps = {
    values: AdminClientsFiltersForm;
    onApply: (values: AdminClientsFiltersForm) => void;
    onReset: () => void;
};

const inputClassName = "rounded-md border border-gray-300 px-3 py-2 focus:border-secondary-500 focus:outline-none";

const AdminClientsFilters = ({ values, onApply, onReset }: AdminClientsFiltersProps) => {
    const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<AdminClientsFiltersForm>({
        values,
    });

    useEffect(() => {
        reset(values);
    }, [reset, values]);

    const positiveOptional = (value: string) => value === "" || (Number.isInteger(Number(value)) && Number(value) >= 1) || "Use a positive integer.";
    const limitRange = (value: string) => (Number.isInteger(Number(value)) && Number(value) >= 1 && Number(value) <= 100) || "Use a value between 1 and 100.";

    return (
        <form
            className="mb-8 rounded-2xl bg-white p-5 shadow-sm"
            onSubmit={handleSubmit(onApply)}
        >
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label className="flex flex-col gap-1 text-sm font-semibold">
                    Min age
                    <input className={inputClassName} inputMode="numeric" {...register("minAge", { validate: positiveOptional })} />
                    {errors.minAge ? <span className="text-xs text-red-600">{errors.minAge.message}</span> : null}
                </label>
                <label className="flex flex-col gap-1 text-sm font-semibold">
                    Max age
                    <input className={inputClassName} inputMode="numeric" {...register("maxAge", { validate: positiveOptional })} />
                    {errors.maxAge ? <span className="text-xs text-red-600">{errors.maxAge.message}</span> : null}
                </label>
                <label className="flex flex-col gap-1 text-sm font-semibold">
                    Min balance
                    <input className={inputClassName} inputMode="numeric" {...register("minBalance", { validate: positiveOptional })} />
                    {errors.minBalance ? <span className="text-xs text-red-600">{errors.minBalance.message}</span> : null}
                </label>
                <label className="flex flex-col gap-1 text-sm font-semibold">
                    Max balance
                    <input className={inputClassName} inputMode="numeric" {...register("maxBalance", { validate: positiveOptional })} />
                    {errors.maxBalance ? <span className="text-xs text-red-600">{errors.maxBalance.message}</span> : null}
                </label>
                <label className="flex flex-col gap-1 text-sm font-semibold">
                    Deleted state
                    <select className={inputClassName} {...register("isDeleted")}>
                        <option value="">All clients</option>
                        <option value="false">Active records</option>
                        <option value="true">Deleted records</option>
                    </select>
                </label>
                <label className="flex flex-col gap-1 text-sm font-semibold">
                    Sort
                    <select className={inputClassName} {...register("sort")}>
                        {SORT_OPTIONS.map((option) => (
                            <option key={option.value} value={option.value}>{option.label}</option>
                        ))}
                    </select>
                </label>
                <label className="flex flex-col gap-1 text-sm font-semibold">
                    Page size
                    <input className={inputClassName} inputMode="numeric" {...register("limit", { required: "Page size is required.", validate: limitRange })} />
                    {errors.limit ? <span className="text-xs text-red-600">{errors.limit.message}</span> : null}
                </label>
            </div>
            <div className="mt-5 flex flex-wrap gap-3">
                <button type="submit" disabled={isSubmitting} className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:opacity-50">
                    Apply filters
                </button>
                <button type="button" className="rounded-md border border-gray-300 bg-white px-5 py-2 font-semibold transition hover:border-secondary-500" onClick={onReset}>
                    Reset
                </button>
            </div>
        </form>
    );
};

export default AdminClientsFilters;
