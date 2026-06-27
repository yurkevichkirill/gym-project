'use client'

import { useFieldArray, useForm } from "react-hook-form";
import { observer } from "mobx-react-lite";
import { useStore } from "@/store/StoreProvider";
import type { AdminClientImportRequest } from "@/types/admin/admin-client.type";

type ImportRow = {
    age: string;
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
};

type FormValues = {
    clients: ImportRow[];
};

const emptyRow = (): ImportRow => ({ age: "", firstName: "", lastName: "", email: "", phone: "" });
const inputClassName = "rounded-md border border-gray-300 px-3 py-2 focus:border-secondary-500 focus:outline-none";
const phonePattern = /^\+?[1-9]\d{4,14}$/;

const AdminClientsImportForm = observer(() => {
    const { adminClientsStore } = useStore();
    const { register, control, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<FormValues>({
        defaultValues: { clients: [emptyRow()] },
    });
    const { fields, append, remove } = useFieldArray({ control, name: "clients" });

    const onSubmit = async (values: FormValues) => {
        const payload: AdminClientImportRequest = {
            clients: values.clients.map((client) => ({
                age: Number(client.age),
                firstName: client.firstName.trim(),
                lastName: client.lastName.trim(),
                email: client.email.trim(),
                phone: client.phone.trim(),
            })),
        };

        await adminClientsStore.import(payload);
        reset({ clients: [emptyRow()] });
    };

    return (
        <form className="rounded-2xl bg-white p-5 shadow-sm" onSubmit={handleSubmit(onSubmit)}>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h2 className="text-xl font-bold">Import clients</h2>
                <button type="button" className="rounded-md border border-gray-300 px-4 py-2 font-semibold transition hover:border-secondary-500" onClick={() => append(emptyRow())}>
                    Add row
                </button>
            </div>
            <div className="mt-4 grid gap-4">
                {fields.map((field, index) => (
                    <div key={field.id} className="grid gap-3 rounded-xl border border-gray-100 p-3 md:grid-cols-5">
                        <input className={inputClassName} placeholder="First name" {...register(`clients.${index}.firstName`, { required: "Required" })} />
                        <input className={inputClassName} placeholder="Last name" {...register(`clients.${index}.lastName`, { required: "Required" })} />
                        <input className={inputClassName} type="email" placeholder="Email" {...register(`clients.${index}.email`, { required: "Required" })} />
                        <input className={inputClassName} placeholder="Phone" {...register(`clients.${index}.phone`, { required: "Required", pattern: { value: phonePattern, message: "Invalid phone" } })} />
                        <div className="flex gap-2">
                            <input className={`${inputClassName} min-w-0 flex-1`} inputMode="numeric" placeholder="Age" {...register(`clients.${index}.age`, { required: "Required", validate: (value) => Number.isInteger(Number(value)) && Number(value) > 0 || "Invalid age" })} />
                            <button type="button" disabled={fields.length === 1} className="rounded-md border border-gray-300 px-3 font-semibold disabled:opacity-50" onClick={() => remove(index)}>
                                Remove
                            </button>
                        </div>
                        {errors.clients?.[index] ? <p className="text-xs text-red-600 md:col-span-5">Check all fields in this row.</p> : null}
                    </div>
                ))}
            </div>
            {adminClientsStore.importResult ? (
                <p className="mt-4 rounded-md bg-emerald-50 p-3 text-sm font-semibold text-emerald-700">
                    Import queued: job #{adminClientsStore.importResult.jobId}, {adminClientsStore.importResult.count} clients.
                </p>
            ) : null}
            <button type="submit" disabled={isSubmitting || adminClientsStore.isImporting} className="mt-5 rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:opacity-50">
                {adminClientsStore.isImporting ? "Queueing..." : "Queue import"}
            </button>
        </form>
    );
});

export default AdminClientsImportForm;
