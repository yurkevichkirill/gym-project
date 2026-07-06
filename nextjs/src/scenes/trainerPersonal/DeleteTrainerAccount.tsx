'use client';

import { getErrorMessage } from "@/lib/getErrorMessage";
import { notify } from "@/lib/notify";
import Section, { errorStateClassName, primaryActionClassName } from "@/shared/Section";
import ConfirmDialog from "@/shared/ui/ConfirmDialog";
import { useStore } from "@/store/StoreProvider";
import { observer } from "mobx-react-lite";
import { useRouter } from "next/navigation";
import { useState } from "react";

const DeleteTrainerAccount = observer(() => {
    const { trainerStore } = useStore();
    const router = useRouter();
    const [error, setError] = useState<string | null>(null);
    const [isConfirmOpen, setIsConfirmOpen] = useState(false);

    const handleDelete = async (): Promise<void> => {
        setError(null);
        const toastId = notify.loading("Deleting trainer account...");

        try {
            await trainerStore.delete();
            notify.success(
                "Account deleted",
                "Your trainer account was removed.",
                toastId,
            );
            setIsConfirmOpen(false);
            router.replace("/");
        } catch (deleteError: unknown) {
            const message = getErrorMessage(
                deleteError,
                "Failed to delete the trainer account.",
            );
            setError(message);
            notify.error("Deletion failed", message, toastId);
            setIsConfirmOpen(false);
        }
    };

    return (
        <Section
            title="Delete account"
            description="Deletion is permanent and can be rejected by the API while the trainer has active bookings, unsettled payments, or a non-zero balance."
            className="border-primary-300"
        >
            {error ? (
                <div className={errorStateClassName} role="alert">
                    {error}
                </div>
            ) : null}

            <button
                type="button"
                disabled={trainerStore.isMutating}
                className={`mt-5 ${primaryActionClassName} bg-primary-300`}
                onClick={() => setIsConfirmOpen(true)}
            >
                {trainerStore.isDeleting ? "Deleting..." : "Delete trainer account"}
            </button>

            <ConfirmDialog
                open={isConfirmOpen}
                title="Delete trainer account?"
                description="This action is unavailable while you have active bookings, unsettled payments, or a non-zero balance. If the backend accepts it, your trainer account will be removed."
                confirmLabel="Delete account"
                cancelLabel="Keep account"
                isConfirming={trainerStore.isDeleting}
                tone="danger"
                onConfirm={() => void handleDelete()}
                onCancel={() => {
                    if (!trainerStore.isDeleting) {
                        setIsConfirmOpen(false);
                    }
                }}
            />
        </Section>
    );
});

export default DeleteTrainerAccount;
