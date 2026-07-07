'use client';

import { notify } from "@/lib/notify";
import {
    canCancelTraining,
    canCompleteTraining,
} from "@/scenes/trainerPersonal/trainings/training-display";
import { getTrainerTrainingMutationErrorMessage } from "@/scenes/trainerPersonal/trainings/training-mutation-error";
import { primaryActionClassName } from "@/shared/Section";
import ConfirmDialog from "@/shared/ui/ConfirmDialog";
import { useStore } from "@/store/StoreProvider";
import { TrainerTrainingType } from "@/types/trainer/private/trainer-training.type";
import { observer } from "mobx-react-lite";
import { useState } from "react";

type PendingAction = "cancel" | "complete";

const TrainerTrainingActions = observer(({
    training,
}: {
    training: TrainerTrainingType;
}) => {
    const { trainerTrainingStore } = useStore();
    const [error, setError] = useState<string | null>(null);
    const [pendingAction, setPendingAction] = useState<PendingAction | null>(null);
    const canCancel = canCancelTraining(training.status);
    const canComplete = canCompleteTraining(training.status);
    const isCanceling = trainerTrainingStore.isCanceling(training.id);
    const isCompleting = trainerTrainingStore.isCompleting(training.id);
    const isBusy = trainerTrainingStore.isMutating;

    const handleCancel = async (): Promise<void> => {
        setError(null);
        const toastId = notify.loading(`Canceling training #${training.id}...`);

        try {
            await trainerTrainingStore.cancel(training.id);
            notify.success(
                "Training canceled",
                "The training list and details were reloaded from the server.",
                toastId,
            );
            setPendingAction(null);
        } catch (cancelError: unknown) {
            const message = getTrainerTrainingMutationErrorMessage(
                cancelError,
                "Failed to cancel the training.",
            );

            setError(message);
            notify.error("Training cancellation failed", message, toastId);
            setPendingAction(null);
        }
    };

    const handleComplete = async (): Promise<void> => {
        setError(null);
        const toastId = notify.loading(`Completing training #${training.id}...`);

        try {
            await trainerTrainingStore.complete(training.id);
            notify.success(
                "Training completed",
                "The training list and details were reloaded from the server.",
                toastId,
            );
            setPendingAction(null);
        } catch (completeError: unknown) {
            const message = getTrainerTrainingMutationErrorMessage(
                completeError,
                "Failed to complete the training.",
            );

            setError(message);
            notify.error("Training completion failed", message, toastId);
            setPendingAction(null);
        }
    };

    if (!canCancel && !canComplete) {
        return null;
    }

    const confirmation = pendingAction === "cancel"
        ? {
            title: `Cancel training #${training.id}?`,
            description: `This cancels the training for client #${training.clientId}. The backend may start payment cancellation or refund processing.`,
            confirmLabel: "Cancel training",
            tone: "danger" as const,
            isConfirming: isCanceling,
            onConfirm: handleCancel,
        }
        : pendingAction === "complete"
            ? {
                title: `Complete training #${training.id}?`,
                description: "The backend will reject this action until the training has finished.",
                confirmLabel: "Complete training",
                tone: "default" as const,
                isConfirming: isCompleting,
                onConfirm: handleComplete,
            }
            : null;

    return (
        <div>
            <div className="flex flex-wrap gap-3">
                {canCancel ? (
                    <button
                        type="button"
                        disabled={isBusy}
                        className={`${primaryActionClassName} bg-primary-300`}
                        onClick={() => setPendingAction("cancel")}
                    >
                        {isCanceling ? "Canceling..." : "Cancel training"}
                    </button>
                ) : null}

                {canComplete ? (
                    <button
                        type="button"
                        disabled={isBusy}
                        className={primaryActionClassName}
                        onClick={() => setPendingAction("complete")}
                    >
                        {isCompleting ? "Completing..." : "Complete training"}
                    </button>
                ) : null}
            </div>

            {error ? (
                <p className="mt-3 text-sm text-primary-500" role="alert">
                    {error}
                </p>
            ) : null}

            {confirmation ? (
                <ConfirmDialog
                    open={pendingAction !== null}
                    title={confirmation.title}
                    description={confirmation.description}
                    confirmLabel={confirmation.confirmLabel}
                    cancelLabel="Keep training"
                    tone={confirmation.tone}
                    isConfirming={confirmation.isConfirming}
                    onConfirm={() => void confirmation.onConfirm()}
                    onCancel={() => {
                        if (!isBusy) {
                            setPendingAction(null);
                        }
                    }}
                />
            ) : null}
        </div>
    );
});

export default TrainerTrainingActions;
