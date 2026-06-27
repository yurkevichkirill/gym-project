'use client';

import { ApiClientError } from "@/lib/apiClient";
import { notify } from "@/lib/notify";
import { canUpdateTraining } from "@/scenes/trainerPersonal/trainings/training-display";
import { getTrainerTrainingMutationErrorMessage } from "@/scenes/trainerPersonal/trainings/training-mutation-error";
import { useStore } from "@/store/StoreProvider";
import {
    TrainerTrainingType,
    TrainerTrainingUpdatePayload,
} from "@/types/trainer/private/trainer-training.type";
import { observer } from "mobx-react-lite";
import { useEffect } from "react";
import { useForm } from "react-hook-form";

interface TrainerTrainingUpdateFormValues {
    date: string;
    startTime: string;
}

const isUpdateField = (
    propertyPath: unknown,
): propertyPath is keyof TrainerTrainingUpdateFormValues => {
    return propertyPath === "date" || propertyPath === "startTime";
};

const TrainerTrainingUpdateForm = observer(({
    training,
}: {
    training: TrainerTrainingType;
}) => {
    const { trainerTrainingStore } = useStore();
    const {
        register,
        handleSubmit,
        reset,
        setError,
        clearErrors,
        formState: {
            errors,
            isDirty,
            isSubmitting,
        },
    } = useForm<TrainerTrainingUpdateFormValues>({
        mode: "onBlur",
        defaultValues: {
            date: training.date,
            startTime: training.startTime.slice(0, 5),
        },
    });

    useEffect(() => {
        reset({
            date: training.date,
            startTime: training.startTime.slice(0, 5),
        });
    }, [reset, training.date, training.startTime]);

    if (!canUpdateTraining(training.status)) {
        return (
            <section className="rounded-xl border border-gray-200 bg-gray-50 p-5">
                <h2 className="text-xl font-bold">Reschedule training</h2>
                <p className="mt-2 text-sm text-gray-600">
                    Only scheduled trainings can be updated.
                </p>
            </section>
        );
    }

    const onSubmit = async (
        values: TrainerTrainingUpdateFormValues,
    ): Promise<void> => {
        clearErrors();

        const payload: TrainerTrainingUpdatePayload = {};
        const currentStartTime = training.startTime.slice(0, 5);

        if (values.date !== training.date) {
            payload.date = values.date;
        }

        if (values.startTime !== currentStartTime) {
            payload.startTime = values.startTime;
        }

        if (payload.date === undefined && payload.startTime === undefined) {
            reset({
                date: training.date,
                startTime: currentStartTime,
            });
            return;
        }

        const toastId = notify.loading(`Updating training #${training.id}...`);

        try {
            const updatedTraining = await trainerTrainingStore.update(
                training.id,
                payload,
            );

            reset({
                date: updatedTraining.date,
                startTime: updatedTraining.startTime.slice(0, 5),
            });
            notify.success(
                "Training updated",
                "The training list and detail were reloaded from the server.",
                toastId,
            );
        } catch (error: unknown) {
            let fieldErrorWasApplied = false;

            if (error instanceof ApiClientError && error.status === 422) {
                for (const violation of error.payload.violations ?? []) {
                    if (!isUpdateField(violation.propertyPath)) {
                        continue;
                    }

                    setError(violation.propertyPath, {
                        type: "server",
                        message: violation.title || violation.message || "Invalid value.",
                    });
                    fieldErrorWasApplied = true;
                }
            }

            if (fieldErrorWasApplied) {
                notify.dismiss(toastId);
                return;
            }

            const message = getTrainerTrainingMutationErrorMessage(
                error,
                "Failed to update the training.",
            );

            setError("root.server", {
                type: "server",
                message,
            });
            notify.error("Training update failed", message, toastId);
        }
    };

    const isBusy = isSubmitting || trainerTrainingStore.isMutating;

    return (
        <section className="rounded-xl border border-gray-200 bg-gray-50 p-5">
            <h2 className="text-xl font-bold">Reschedule training</h2>
            <p className="mt-2 text-sm text-gray-600">
                The form matches TrainingUpdateRequestDTO and changes only the date, start time, or both.
            </p>

            <form
                className="mt-5 grid gap-5 sm:grid-cols-2"
                onSubmit={handleSubmit(onSubmit)}
                noValidate
            >
                <div>
                    <label
                        htmlFor={`trainer-training-date-${training.id}`}
                        className="mb-1 block font-medium"
                    >
                        Date
                    </label>
                    <input
                        id={`trainer-training-date-${training.id}`}
                        type="date"
                        className={`w-full rounded-md border px-3 py-2 outline-none ${
                            errors.date ? "border-primary-500" : "border-secondary-500"
                        }`}
                        aria-invalid={errors.date ? "true" : "false"}
                        aria-describedby={
                            errors.date
                                ? `trainer-training-date-${training.id}-error`
                                : undefined
                        }
                        {...register("date", {
                            required: "Date is required.",
                        })}
                    />
                    {errors.date ? (
                        <p
                            id={`trainer-training-date-${training.id}-error`}
                            className="mt-1 text-sm text-primary-500"
                            role="alert"
                        >
                            {errors.date.message}
                        </p>
                    ) : null}
                </div>

                <div>
                    <label
                        htmlFor={`trainer-training-start-${training.id}`}
                        className="mb-1 block font-medium"
                    >
                        Start time
                    </label>
                    <input
                        id={`trainer-training-start-${training.id}`}
                        type="time"
                        step={60}
                        className={`w-full rounded-md border px-3 py-2 outline-none ${
                            errors.startTime
                                ? "border-primary-500"
                                : "border-secondary-500"
                        }`}
                        aria-invalid={errors.startTime ? "true" : "false"}
                        aria-describedby={
                            errors.startTime
                                ? `trainer-training-start-${training.id}-error`
                                : undefined
                        }
                        {...register("startTime", {
                            required: "Start time is required.",
                        })}
                    />
                    {errors.startTime ? (
                        <p
                            id={`trainer-training-start-${training.id}-error`}
                            className="mt-1 text-sm text-primary-500"
                            role="alert"
                        >
                            {errors.startTime.message}
                        </p>
                    ) : null}
                </div>

                {errors.root?.server ? (
                    <p className="text-sm text-primary-500 sm:col-span-2" role="alert">
                        {errors.root.server.message}
                    </p>
                ) : null}

                <div className="flex flex-wrap gap-3 sm:col-span-2">
                    <button
                        type="submit"
                        disabled={isBusy || !isDirty}
                        className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {trainerTrainingStore.isUpdating(training.id)
                            ? "Updating..."
                            : "Update training"}
                    </button>
                    <button
                        type="button"
                        disabled={isBusy || !isDirty}
                        className="rounded-md border border-gray-300 bg-white px-5 py-2 font-semibold transition hover:border-secondary-500 disabled:cursor-not-allowed disabled:opacity-50"
                        onClick={() => reset({
                            date: training.date,
                            startTime: training.startTime.slice(0, 5),
                        })}
                    >
                        Reset
                    </button>
                </div>
            </form>
        </section>
    );
});

export default TrainerTrainingUpdateForm;
