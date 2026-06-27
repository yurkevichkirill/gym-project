'use client';

import { notify } from "@/lib/notify";
import {
    canCancelTraining,
    canCompleteTraining,
} from "@/scenes/trainerPersonal/trainings/training-display";
import { getTrainerTrainingMutationErrorMessage } from "@/scenes/trainerPersonal/trainings/training-mutation-error";
import { useStore } from "@/store/StoreProvider";
import { TrainerTrainingType } from "@/types/trainer/private/trainer-training.type";
import { observer } from "mobx-react-lite";
import { useState } from "react";

const TrainerTrainingActions = observer(({
    training,
}: {
    training: TrainerTrainingType;
}) => {
    const { trainerTrainingStore } = useStore();
    const [error, setError] = useState<string | null>(null);
    const canCancel = canCancelTraining(training.status);
    const canComplete = canCompleteTraining(training.status);
    const isCanceling = trainerTrainingStore.isCanceling(training.id);
    const isCompleting = trainerTrainingStore.isCompleting(training.id);
    const isBusy = trainerTrainingStore.isMutating;

    const handleCancel = async (): Promise<void> => {
        if (!window.confirm(
            `Cancel training #${training.id} for client #${training.clientId}? This may trigger payment cancellation or refund processing.`,
        )) {
            return;
        }

        setError(null);
        const toastId = notify.loading(`Canceling training #${training.id}...`);

        try {
            await trainerTrainingStore.cancel(training.id);
            notify.success(
                "Training canceled",
                "The training list and details were reloaded from the server.",
                toastId,
            );
        } catch (cancelError: unknown) {
            const message = getTrainerTrainingMutationErrorMessage(
                cancelError,
                "Failed to cancel the training.",
            );

            setError(message);
            notify.error("Training cancellation failed", message, toastId);
        }
    };

    const handleComplete = async (): Promise<void> => {
        if (!window.confirm(
            `Mark training #${training.id} as completed? The backend will reject this action until the training has finished.`,
        )) {
            return;
        }

        setError(null);
        const toastId = notify.loading(`Completing training #${training.id}...`);

        try {
            await trainerTrainingStore.complete(training.id);
            notify.success(
                "Training completed",
                "The training list and details were reloaded from the server.",
                toastId,
            );
        } catch (completeError: unknown) {
            const message = getTrainerTrainingMutationErrorMessage(
                completeError,
                "Failed to complete the training.",
            );

            setError(message);
            notify.error("Training completion failed", message, toastId);
        }
    };

    if (!canCancel && !canComplete) {
        return null;
    }

    return (
        <div>
            <div className="flex flex-wrap gap-3">
                {canCancel ? (
                    <button
                        type="button"
                        disabled={isBusy}
                        className="rounded-md bg-primary-300 px-4 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                        onClick={() => void handleCancel()}
                    >
                        {isCanceling ? "Canceling..." : "Cancel training"}
                    </button>
                ) : null}

                {canComplete ? (
                    <button
                        type="button"
                        disabled={isBusy}
                        className="rounded-md bg-secondary-500 px-4 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                        onClick={() => void handleComplete()}
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
        </div>
    );
});

export default TrainerTrainingActions;
