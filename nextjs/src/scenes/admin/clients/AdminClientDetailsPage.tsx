'use client'

import { useEffect, useState } from "react";
import Link from "next/link";
import { useForm } from "react-hook-form";
import { observer } from "mobx-react-lite";
import { useStore } from "@/store/StoreProvider";
import EmptyState from "@/shared/ui/EmptyState";
import ErrorState from "@/shared/ui/ErrorState";
import LoadingState from "@/shared/ui/LoadingState";
import ConfirmDialog from "@/shared/ui/ConfirmDialog";
import type { AdminClientUpdateRequest } from "@/types/admin/admin-client.type";
import { formatAdminClientDate, formatAdminClientMoney, getAdminClientStateClassName, getAdminClientStateLabel } from "@/scenes/admin/clients/admin-client-display";

type AdminClientDetailsPageProps = {
    clientId: number;
};

type FormValues = {
    age: string;
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
    password: string;
};

type ConfirmAction = "delete" | "restore" | "block" | "unblock" | "visit";

const inputClassName = "rounded-md border border-gray-300 px-3 py-2 focus:border-secondary-500 focus:outline-none";
const phonePattern = /^\+?[1-9]\d{4,14}$/;

const DetailRow = ({ label, value }: { label: string; value: string }) => (
    <div className="flex flex-col gap-1 border-b border-gray-100 py-3 last:border-b-0 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
        <dt className="text-sm text-gray-500">{label}</dt>
        <dd className="break-all font-semibold sm:text-right">{value}</dd>
    </div>
);

const getConfirmCopy = (action: ConfirmAction) => {
    switch (action) {
        case "delete":
            return { title: "Delete client?", description: "This soft-deletes the client account. It can be restored later, but access is removed immediately.", label: "Delete", tone: "danger" as const };
        case "restore":
            return { title: "Restore client?", description: "This restores the soft-deleted client account.", label: "Restore", tone: "default" as const };
        case "block":
            return { title: "Block client?", description: "This prevents the client from using protected client functionality.", label: "Block", tone: "danger" as const };
        case "unblock":
            return { title: "Unblock client?", description: "This restores access for the blocked client account.", label: "Unblock", tone: "default" as const };
        case "visit":
            return { title: "Register visit?", description: "This writes off one visit from the client's active membership. This operation should not be repeated accidentally.", label: "Register visit", tone: "danger" as const };
    }
};

const AdminClientDetailsPage = observer(({ clientId }: AdminClientDetailsPageProps) => {
    const { adminClientsStore } = useStore();
    const [confirmAction, setConfirmAction] = useState<ConfirmAction | null>(null);
    const client = adminClientsStore.selectedClient?.id === clientId ? adminClientsStore.selectedClient : null;
    const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<FormValues>({
        defaultValues: { age: "", firstName: "", lastName: "", email: "", phone: "", password: "" },
    });

    useEffect(() => {
        void adminClientsStore.loadClient(clientId);
    }, [adminClientsStore, clientId]);

    useEffect(() => {
        if (client === null) {
            return;
        }

        reset({
            age: client.age.toString(),
            firstName: client.firstName,
            lastName: client.lastName,
            email: client.email,
            phone: client.phone,
            password: "",
        });
    }, [client, reset]);

    const onSubmit = async (values: FormValues) => {
        const payload: AdminClientUpdateRequest = {
            age: Number(values.age),
            firstName: values.firstName.trim(),
            lastName: values.lastName.trim(),
            email: values.email.trim(),
            phone: values.phone.trim(),
            ...(values.password.length > 0 ? { password: values.password } : {}),
        };

        await adminClientsStore.update(clientId, payload);
    };

    const runConfirmedAction = async () => {
        if (confirmAction === null) {
            return;
        }

        if (confirmAction === "delete") {
            await adminClientsStore.delete(clientId);
        } else if (confirmAction === "restore") {
            await adminClientsStore.restore(clientId);
        } else if (confirmAction === "block") {
            await adminClientsStore.block(clientId);
        } else if (confirmAction === "unblock") {
            await adminClientsStore.unblock(clientId);
        } else {
            await adminClientsStore.visit(clientId);
        }

        setConfirmAction(null);
    };

    if (client === null && adminClientsStore.isDetailLoading) {
        return <LoadingState title="Loading client..." description="We are fetching the client record." />;
    }

    if (client === null && adminClientsStore.detailErrorStatus === 404) {
        return <EmptyState title="Client not found" description="This client does not exist or is unavailable." action={<Link href="/admin/clients" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold">Back to clients</Link>} />;
    }

    if (client === null && adminClientsStore.detailErrorStatus === 403) {
        return <EmptyState title="Access denied" description="Your account is not allowed to view this client." action={<Link href="/admin/clients" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold">Back to clients</Link>} />;
    }

    if (client === null && adminClientsStore.detailError) {
        return <ErrorState title="Unable to load client" message={adminClientsStore.detailError} isRetrying={adminClientsStore.isDetailLoading} onRetry={() => void adminClientsStore.loadClient(clientId)} />;
    }

    if (client === null) {
        return <LoadingState title="Loading client..." />;
    }

    const isDeleted = client.deletedAt.length > 0;
    const isBlocked = client.blockedAt.length > 0;
    const currentConfirm = confirmAction !== null ? getConfirmCopy(confirmAction) : null;
    const isConfirming = confirmAction !== null && adminClientsStore.isActionRunning(clientId, confirmAction);

    return (
        <section className="mx-auto w-full max-w-6xl" aria-busy={adminClientsStore.isDetailLoading}>
            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <Link href="/admin/clients" className="rounded-md border border-gray-300 bg-white px-4 py-2 font-semibold transition hover:border-secondary-500">
                    Back to clients
                </Link>
                {adminClientsStore.isDetailLoading ? <p role="status" className="text-sm font-semibold text-secondary-500">Refreshing client...</p> : null}
            </div>

            {adminClientsStore.mutationError ? <div role="alert" className="mb-6 rounded-xl bg-red-50 p-4 text-red-700">{adminClientsStore.mutationError}</div> : null}

            <article className="rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                <div className="flex flex-wrap items-start justify-between gap-5">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">Client #{client.id}</p>
                        <h1 className="mt-2 text-3xl font-bold">{client.firstName} {client.lastName}</h1>
                        <p className="mt-2 break-all text-gray-500">{client.email}</p>
                    </div>
                    <span className={`rounded-full px-4 py-2 text-sm font-semibold ${getAdminClientStateClassName(client)}`}>
                        {getAdminClientStateLabel(client)}
                    </span>
                </div>

                <div className="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(320px,0.8fr)]">
                    <form className="rounded-xl border border-gray-100 p-5" onSubmit={handleSubmit(onSubmit)}>
                        <h2 className="text-xl font-bold">Edit profile</h2>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <label className="flex flex-col gap-1 text-sm font-semibold">First name<input className={inputClassName} {...register("firstName", { required: "First name is required." })} />{errors.firstName ? <span className="text-xs text-red-600">{errors.firstName.message}</span> : null}</label>
                            <label className="flex flex-col gap-1 text-sm font-semibold">Last name<input className={inputClassName} {...register("lastName", { required: "Last name is required." })} />{errors.lastName ? <span className="text-xs text-red-600">{errors.lastName.message}</span> : null}</label>
                            <label className="flex flex-col gap-1 text-sm font-semibold">Email<input className={inputClassName} type="email" {...register("email", { required: "Email is required." })} />{errors.email ? <span className="text-xs text-red-600">{errors.email.message}</span> : null}</label>
                            <label className="flex flex-col gap-1 text-sm font-semibold">Phone<input className={inputClassName} {...register("phone", { required: "Phone is required.", pattern: { value: phonePattern, message: "Use E.164-like phone format." } })} />{errors.phone ? <span className="text-xs text-red-600">{errors.phone.message}</span> : null}</label>
                            <label className="flex flex-col gap-1 text-sm font-semibold">Age<input className={inputClassName} inputMode="numeric" {...register("age", { required: "Age is required.", validate: (value) => Number.isInteger(Number(value)) && Number(value) > 0 || "Use a positive integer." })} />{errors.age ? <span className="text-xs text-red-600">{errors.age.message}</span> : null}</label>
                            <label className="flex flex-col gap-1 text-sm font-semibold">New password<input className={inputClassName} type="password" autoComplete="new-password" {...register("password", { validate: (value) => value === "" || value.length >= 8 || "Use at least 8 characters." })} />{errors.password ? <span className="text-xs text-red-600">{errors.password.message}</span> : null}</label>
                        </div>
                        <button type="submit" disabled={isSubmitting || adminClientsStore.isUpdating} className="mt-5 rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:opacity-50">
                            {adminClientsStore.isUpdating ? "Saving..." : "Save changes"}
                        </button>
                    </form>

                    <div className="grid content-start gap-6">
                        <section className="rounded-xl border border-gray-100 p-5">
                            <h2 className="text-xl font-bold">Account</h2>
                            <dl className="mt-3">
                                <DetailRow label="Balance" value={formatAdminClientMoney(client.balance)} />
                                <DetailRow label="Created" value={formatAdminClientDate(client.createdAt)} />
                                <DetailRow label="Updated" value={formatAdminClientDate(client.updatedAt)} />
                                <DetailRow label="Blocked" value={formatAdminClientDate(client.blockedAt)} />
                                <DetailRow label="Deleted" value={formatAdminClientDate(client.deletedAt)} />
                            </dl>
                        </section>

                        <section className="rounded-xl border border-gray-100 p-5">
                            <h2 className="text-xl font-bold">Actions</h2>
                            <div className="mt-4 flex flex-wrap gap-3">
                                <button type="button" disabled={isDeleted || adminClientsStore.isActionRunning(clientId, "visit")} className="rounded-md bg-secondary-500 px-4 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:opacity-50" onClick={() => setConfirmAction("visit")}>Register visit</button>
                                {isBlocked ? <button type="button" disabled={isDeleted || adminClientsStore.isActionRunning(clientId, "unblock")} className="rounded-md border border-gray-300 px-4 py-2 font-semibold" onClick={() => setConfirmAction("unblock")}>Unblock</button> : <button type="button" disabled={isDeleted || adminClientsStore.isActionRunning(clientId, "block")} className="rounded-md border border-red-300 px-4 py-2 font-semibold text-red-700 disabled:opacity-50" onClick={() => setConfirmAction("block")}>Block</button>}
                                {isDeleted ? <button type="button" disabled={adminClientsStore.isActionRunning(clientId, "restore")} className="rounded-md border border-gray-300 px-4 py-2 font-semibold" onClick={() => setConfirmAction("restore")}>Restore</button> : <button type="button" disabled={adminClientsStore.isActionRunning(clientId, "delete")} className="rounded-md bg-red-600 px-4 py-2 font-semibold text-white disabled:opacity-50" onClick={() => setConfirmAction("delete")}>Delete</button>}
                            </div>
                        </section>
                    </div>
                </div>
            </article>

            {currentConfirm ? (
                <ConfirmDialog
                    open={confirmAction !== null}
                    title={currentConfirm.title}
                    description={currentConfirm.description}
                    confirmLabel={currentConfirm.label}
                    tone={currentConfirm.tone}
                    isConfirming={isConfirming}
                    onConfirm={() => void runConfirmedAction()}
                    onCancel={() => setConfirmAction(null)}
                />
            ) : null}
        </section>
    );
});

export default AdminClientDetailsPage;
