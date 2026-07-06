'use client'

import { useRouter, useSearchParams } from "next/navigation";
import { useId, useState } from "react";
import { useForm } from "react-hook-form";
import { secondaryActionClassName, primaryActionClassName } from "@/shared/Section";

export type AdminFilterField = {
    name: string;
    label: string;
    type?: "text" | "number" | "date" | "time" | "datetime-local" | "select";
    options?: { label: string; value: string }[];
};

type AdminListToolbarProps = {
    fields: AdminFilterField[];
    sortOptions: { label: string; value: string }[];
    defaultSort: string;
};

type FormValues = Record<string, string>;

const inputClassName = "rounded-md border border-gray-100 bg-gray-20 px-3 py-2 font-normal text-gray-500 transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20";

const AdminListToolbar = ({ fields, sortOptions, defaultSort }: AdminListToolbarProps) => {
    const router = useRouter();
    const searchParams = useSearchParams();
    const panelId = useId();
    const [open, setOpen] = useState(false);
    const values = Object.fromEntries([
        ...fields.map((field) => [field.name, searchParams.get(field.name) ?? ""]),
        ["sort", searchParams.get("sort") ?? defaultSort],
        ["limit", searchParams.get("limit") ?? "20"],
    ]);
    const { register, handleSubmit, reset } = useForm<FormValues>({ values });
    const activeFilters = fields.some((field) => searchParams.get(field.name));

    const pushParams = (nextValues: FormValues) => {
        const next = new URLSearchParams(searchParams.toString());

        [...fields.map((field) => field.name), "sort", "limit"].forEach((key) => {
            const value = nextValues[key]?.trim();

            if (value) {
                next.set(key, value);
            } else {
                next.delete(key);
            }
        });

        next.delete("page");
        router.push(`?${next.toString()}`);
    };

    const clear = () => {
        reset(Object.fromEntries([
            ...fields.map((field) => [field.name, ""]),
            ["sort", defaultSort],
            ["limit", "20"],
        ]));
        router.push("?");
    };

    return (
        <div className="mb-6 border-y border-gray-100 py-4">
            <div className="flex flex-wrap items-center justify-between gap-4">
                <button
                    type="button"
                    className={secondaryActionClassName}
                    aria-expanded={open}
                    aria-controls={panelId}
                    onClick={() => setOpen((current) => !current)}
                >
                    {activeFilters ? "Filters active" : "Filters"}
                </button>
                <p className="text-sm text-gray-600">Server-side filters, sorting and pagination are reflected in the URL.</p>
            </div>
            {open ? (
                <form id={panelId} className="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" onSubmit={handleSubmit(pushParams)}>
                    {fields.map((field) => (
                        <label key={field.name} className="flex flex-col gap-2 text-sm font-semibold text-gray-500">
                            {field.label}
                            {field.type === "select" ? (
                                <select className={inputClassName} {...register(field.name)}>
                                    <option value="">Any</option>
                                    {field.options?.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                            ) : (
                                <input className={inputClassName} type={field.type ?? "text"} {...register(field.name)} />
                            )}
                        </label>
                    ))}
                    <label className="flex flex-col gap-2 text-sm font-semibold text-gray-500">
                        Sort
                        <select className={inputClassName} {...register("sort")}>
                            {sortOptions.map((option) => (
                                <option key={option.value} value={option.value}>{option.label}</option>
                            ))}
                        </select>
                    </label>
                    <label className="flex flex-col gap-2 text-sm font-semibold text-gray-500">
                        Page size
                        <select className={inputClassName} {...register("limit")}>
                            {[10, 20, 50, 100].map((limit) => (
                                <option key={limit} value={limit}>{limit}</option>
                            ))}
                        </select>
                    </label>
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <button type="submit" className={primaryActionClassName}>Apply</button>
                        <button type="button" className={secondaryActionClassName} onClick={clear}>Clear</button>
                    </div>
                </form>
            ) : null}
        </div>
    );
};

export default AdminListToolbar;
