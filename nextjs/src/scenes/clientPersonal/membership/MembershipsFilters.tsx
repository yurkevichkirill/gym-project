'use client'

import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { DEFAULT_MEMBERSHIPS_SORT } from "@/api/client/memberships.api";
import { MembershipStatusEnum } from "@/types/membership/membership-status.enum";
import { getMembershipStatusLabel } from "@/scenes/clientPersonal/membership/membership-display";

const SORT_OPTIONS = [
    { value: "startDate:ASC", label: "Start date (earliest first)" },
    { value: "startDate:DESC", label: "Start date (latest first)" },
    { value: "endDate:ASC", label: "End date (earliest first)" },
    { value: "endDate:DESC", label: "End date (latest first)" },
    { value: "status:ASC", label: "Status (A–Z)" },
    { value: "status:DESC", label: "Status (Z–A)" },
    { value: "visits:ASC", label: "Visits used (lowest first)" },
    { value: "visits:DESC", label: "Visits used (highest first)" },
    { value: "membershipPlanId:ASC", label: "Plan ID (ascending)" },
    { value: "membershipPlanId:DESC", label: "Plan ID (descending)" },
] as const;

export type MembershipsFiltersForm = {
    membershipPlanId: string;
    status: string;
    minVisits: string;
    maxVisits: string;
    sort: string;
    limit: string;
};

type MembershipsFiltersProps = {
    values: MembershipsFiltersForm;
    onApply: (values: MembershipsFiltersForm) => void;
    onReset: () => void;
};

const inputClassName = "rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none";
const labelClassName = "flex flex-col gap-2 text-sm font-semibold";

const MembershipsFilters = ({ values, onApply, onReset }: MembershipsFiltersProps) => {
    const {
        register,
        handleSubmit,
        reset,
        formState: { errors },
    } = useForm<MembershipsFiltersForm>({ defaultValues: values });

    useEffect(() => {
        reset(values);
    }, [reset, values]);

    const positiveInteger = (value: string) => value === ""
        || (Number.isInteger(Number(value)) && Number(value) > 0)
        || "Enter a positive whole number.";
    const nonNegativeInteger = (value: string) => value === ""
        || (Number.isInteger(Number(value)) && Number(value) >= 0)
        || "Enter zero or a positive whole number.";
    const limit = (value: string) => value === ""
        || (Number.isInteger(Number(value)) && Number(value) >= 1 && Number(value) <= 100)
        || "Use a value between 1 and 100.";
    const hasCustomSort = !SORT_OPTIONS.some((option) => option.value === values.sort);

    return (
        <form
            className="mb-8 rounded-2xl bg-white p-5 shadow-sm sm:p-6"
            onSubmit={handleSubmit(onApply)}
            noValidate
        >
            <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <label className={labelClassName}>
                    Membership plan ID
                    <input
                        type="number"
                        min="1"
                        step="1"
                        inputMode="numeric"
                        className={inputClassName}
                        {...register("membershipPlanId", { validate: positiveInteger })}
                    />
                    {errors.membershipPlanId ? (
                        <span className="font-normal text-red-600">{errors.membershipPlanId.message}</span>
                    ) : null}
                </label>

                <label className={labelClassName}>
                    Status
                    <select className={inputClassName} {...register("status")}>
                        <option value="">All statuses</option>
                        {Object.values(MembershipStatusEnum).map((status) => (
                            <option key={status} value={status}>{getMembershipStatusLabel(status)}</option>
                        ))}
                    </select>
                </label>

                <label className={labelClassName}>
                    Minimum visits used
                    <input
                        type="number"
                        min="0"
                        step="1"
                        inputMode="numeric"
                        className={inputClassName}
                        {...register("minVisits", { validate: nonNegativeInteger })}
                    />
                    {errors.minVisits ? (
                        <span className="font-normal text-red-600">{errors.minVisits.message}</span>
                    ) : null}
                </label>

                <label className={labelClassName}>
                    Maximum visits used
                    <input
                        type="number"
                        min="0"
                        step="1"
                        inputMode="numeric"
                        className={inputClassName}
                        {...register("maxVisits", { validate: nonNegativeInteger })}
                    />
                    {errors.maxVisits ? (
                        <span className="font-normal text-red-600">{errors.maxVisits.message}</span>
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

export const toMembershipsFilterValues = (params: {
    membershipPlanId?: number;
    status?: MembershipStatusEnum;
    minVisits?: number;
    maxVisits?: number;
    sort?: string;
    limit?: number;
}): MembershipsFiltersForm => ({
    membershipPlanId: params.membershipPlanId?.toString() ?? "",
    status: params.status ?? "",
    minVisits: params.minVisits?.toString() ?? "",
    maxVisits: params.maxVisits?.toString() ?? "",
    sort: params.sort ?? DEFAULT_MEMBERSHIPS_SORT,
    limit: params.limit?.toString() ?? "",
});

export default MembershipsFilters;
