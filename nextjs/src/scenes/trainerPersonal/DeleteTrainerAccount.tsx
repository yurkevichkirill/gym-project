'use client';

import { getErrorMessage } from "@/lib/getErrorMessage";
import { notify } from "@/lib/notify";
import { useStore } from "@/store/StoreProvider";
import { observer } from "mobx-react-lite";
import { useRouter } from "next/navigation";
import { useState } from "react";

const DeleteTrainerAccount = observer(() => {
    const { trainerStore } = useStore();
    const router = useRouter();
    const [error, setError] = useState<string | null>(null);

    const handleDelete = async (): Promise<void> => {
        if (!window.confirm(
            "Delete your trainer account? This action is unavailable while you have active bookings, unsettled payments, or a non-zero balance.",
        )) {
            return;
        }

        setError(null);
        const toastId = notify.loading("Deleting trainer account...");

        try {
            await trainerStore.delete();
            notify.success(
                "Account deleted",
                "Your trainer account was removed.",
                toastId,
            );
            router.replace("/");
        } catch (deleteError: unknown) {
            const message = getErrorMessage(
                deleteError,
                "Failed to delete the trainer account.",
            );
            setError(message);
            notify.error("Deletion failed", message, toastId);
        }
    };

    return (
        <section className="rounded-2xl border border-primary-300 bg-white p-6 shadow-md sm:p-8">
            <h2 className="text-2xl font-bold">Delete account</h2>
            <p className="mt-2 text-sm text-gray-600">
                Deletion is permanent and can be rejected by the API while the trainer
                has active bookings, unsettled payments, or a non-zero balance.
            </p>

            {error && (
                <p className="mt-4 text-sm text-primary-500" role="alert">
                    {error}
                </p>
            )}

            <button
                type="button"
                disabled={trainerStore.isMutating}
                className="mt-5 rounded-md bg-primary-300 px-5 py-2 font-medium transition-colors hover:bg-primary-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                onClick={() => void handleDelete()}
            >
                {trainerStore.isDeleting ? "Deleting..." : "Delete trainer account"}
            </button>
        </section>
    );
});

export default DeleteTrainerAccount;
