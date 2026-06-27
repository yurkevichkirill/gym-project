'use client'

import { useForm } from "react-hook-form";
import { observer } from "mobx-react-lite";
import { useStore } from "@/store/StoreProvider";
import type { AdminClientCreateRequest } from "@/types/admin/admin-client.type";

type FormValues = {
    age: string;
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
    password: string;
};

const inputClassName = "rounded-md border border-gray-300 px-3 py-2 focus:border-secondary-500 focus:outline-none";
const phonePattern = /^\+?[1-9]\d{4,14}$/;

const AdminClientCreateForm = observer(() => {
    const { adminClientsStore } = useStore();
    const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<FormValues>({
        defaultValues: {
            age: "",
            firstName: "",
            lastName: "",
            email: "",
            phone: "",
            password: "",
        },
    });

    const onSubmit = async (values: FormValues) => {
        const payload: AdminClientCreateRequest = {
            age: Number(values.age),
            firstName: values.firstName.trim(),
            lastName: values.lastName.trim(),
            email: values.email.trim(),
            phone: values.phone.trim(),
            password: values.password,
        };

        await adminClientsStore.create(payload);
        reset();
    };

    return (
        <form className="rounded-2xl bg-white p-5 shadow-sm" onSubmit={handleSubmit(onSubmit)}>
            <h2 className="text-xl font-bold">Create client</h2>
            <div className="mt-4 grid gap-4 md:grid-cols-2">
                <label className="flex flex-col gap-1 text-sm font-semibold">
                    First name
                    <input className={inputClassName} {...register("firstName", { required: "First name is required." })} />
                    {errors.firstName ? <span className="text-xs text-red-600">{errors.firstName.message}</span> : null}
                </label>
                <label className="flex flex-col gap-1 text-sm font-semibold">
                    Last name
                    <input className={inputClassName} {...register("lastName", { required: "Last name is required." })} />
                    {errors.lastName ? <span className="text-xs text-red-600">{errors.lastName.message}</span> : null}
                </label>
                <label className="flex flex-col gap-1 text-sm font-semibold">
                    Email
                    <input className={inputClassName} type="email" {...register("email", { required: "Email is required." })} />
                    {errors.email ? <span className="text-xs text-red-600">{errors.email.message}</span> : null}
                </label>
                <label className="flex flex-col gap-1 text-sm font-semibold">
                    Phone
                    <input className={inputClassName} {...register("phone", { required: "Phone is required.", pattern: { value: phonePattern, message: "Use E.164-like phone format." } })} />
                    {errors.phone ? <span className="text-xs text-red-600">{errors.phone.message}</span> : null}
                </label>
                <label className="flex flex-col gap-1 text-sm font-semibold">
                    Age
                    <input className={inputClassName} inputMode="numeric" {...register("age", { required: "Age is required.", validate: (value) => Number.isInteger(Number(value)) && Number(value) > 0 || "Use a positive integer." })} />
                    {errors.age ? <span className="text-xs text-red-600">{errors.age.message}</span> : null}
                </label>
                <label className="flex flex-col gap-1 text-sm font-semibold">
                    Password
                    <input className={inputClassName} type="password" autoComplete="new-password" {...register("password", { required: "Password is required.", minLength: { value: 8, message: "Use at least 8 characters." } })} />
                    {errors.password ? <span className="text-xs text-red-600">{errors.password.message}</span> : null}
                </label>
            </div>
            <button type="submit" disabled={isSubmitting || adminClientsStore.isCreating} className="mt-5 rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:opacity-50">
                {adminClientsStore.isCreating ? "Creating..." : "Create client"}
            </button>
        </form>
    );
});

export default AdminClientCreateForm;
