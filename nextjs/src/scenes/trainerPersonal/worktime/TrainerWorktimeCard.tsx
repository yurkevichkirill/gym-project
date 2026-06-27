'use client';

import { ApiClientError } from "@/lib/apiClient";
import { notify } from "@/lib/notify";
import { getTrainerWorktimeMutationErrorMessage } from "@/scenes/trainerPersonal/worktime/worktime-mutation-error";
import { useStore } from "@/store/StoreProvider";
import { TrainerWorktimeUpdatePayload } from "@/types/trainer/private/trainer-worktime.type";
import WorktimeData from "@/types/trainer/public/worktime.type";
import { observer } from "mobx-react-lite";
import { useState } from "react";
import { useForm } from "react-hook-form";

interface UpdateWorktimeFormValues {
    startTime: string;
    endTime: string;
}

const isUpdateField = (
    propertyPath: unknown,
): propertyPath is keyof UpdateWorktimeFormValues => {
    return propertyPath === "startTime" || propertyPath === "endTime";
};

const TrainerWorktimeCard = observer(({
    worktime,
}: {
    worktime: WorktimeData;
}) => {
    const { trainerWorktimeStore } = useStore();
    const [deleteError, setDeleteError] = useState<string | null>(null);
    const {
        register,
        handleSubmit,
        reset,
        setError,
        clearErrors,
        getValues,
        formState: { errors, isSubmitting },
    } = useForm<UpdateWorktimeFormValues>({
        mode: "onBlur",
        defaultValues: {
            startTime: "",
            endTime: "",
        },
    });

    const onSubmit = async (values: UpdateWorktimeFormValues): Promise<void> => {
        clearErrors();
        setDeleteError(null);

        const payload: TrainerWorktimeUpdatePayload = {};

        if (values.startTime !== "") {
            payload.startTime = values.startTime;
        }

        if (values.endTime !== "") {
            payload.endTime = values.endTime;
        }

        if (payload.startTime === undefined && payload.endTime === undefined) {
            setError("root.server", {
                type: "validate",
                message: "Enter a new start time, end time, or both.",
            });
            return;
        }

        const toastId = notify.loading(`Updating worktime #${worktime.id}...`);

        try {
            await trainerWorktimeStore.update(worktime.id, payload);
            reset();
            notify.success(
                "Worktime updated",
                "The worktime list was reloaded from the server.",
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

            const message = getTrainerWorktimeMutationErrorMessage(
                error,
                "Failed to update the trainer worktime.",
            );

            setError("root.server", {
                type: "server",
                message,
            });
            notify.error("Worktime update failed", message, toastId);
        }
    };

    const handleDelete = async (): Promise<void> => {
        if (!window.confirm(
            `Delete worktime #${worktime.id} on ${worktime.date}? The backend rejects deletion when training history exists.`,
        )) {
            return;
        }

        clearErrors();
        setDeleteError(null);
        const toastId = notify.loading(`Deleting worktime #${worktime.id}...`);

        try {
            await trainerWorktimeStore.remove(worktime.id);
            notify.success(
                "Worktime deleted",
                "The list was reloaded after the server confirmed deletion.",
                toastId,
            );
        } catch (error: unknown) {
            const message = getTrainerWorktimeMutationErrorMessage(
                error,
                "Failed to delete the trainer worktime.",
            );

            setDeleteError(message);
            notify.error("Worktime deletion failed", message, toastId);
        }
    };

    const isBusy = isSubmitting || trainerWorktimeStore.isMutating;
    const isUpdating = trainerWorktimeStore.isUpdating(worktime.id);
    const isDeleting = trainerWorktimeStore.isDeleting(worktime.id);

    return (
        <article className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                        Worktime #{worktime.id}
                    </p>
                    <h3 className="mt-2 text-2xl font-bold">
                        <time dateTime={worktime.date}>{worktime.date}</time>
                    </h3>
                </div>
                <span className="rounded-full bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-700">
                    {worktime.freeSlots.length} free interval{worktime.freeSlots.length === 1 ? "" : "s"}
                </span>
            </div>

            <div className="mt-5">
                <h4 className="font-semibold">Free intervals returned by the API</h4>
                {worktime.freeSlots.length === 0 ? (
                    <p className="mt-2 text-sm text-gray-600">
                        No free intervals are currently available. The worktime may be fully occupied.
                    </p>
                ) : (
                    <ul className="mt-3 flex flex-wrap gap-2">
                        {worktime.freeSlots.map((slot) => (
                            <li
                                key={`${slot.start}-${slot.end}`}
                                className="rounded-md bg-gray-100 px-3 py-2 font-mono text-sm text-gray-800"
                            >
                                <time dateTime={slot.start}>{slot.start}</time>
                                {" — "}
                                <time dateTime={slot.end}>{slot.end}</time>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <form
                className="mt-6 border-t border-gray-200 pt-6"
                onSubmit={handleSubmit(onSubmit)}
                noValidate
            >
                <h4 className="font-semibold">Update interval boundaries</h4>
                <p className="mt-2 text-sm text-gray-600">
                    Leave a field blank to keep that boundary unchanged. The response DTO exposes free slots, not the original start and end boundaries, so values are intentionally not prefilled.
                </p>

                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label
                            htmlFor={`trainer-worktime-start-${worktime.id}`}
                            className="mb-1 block text-sm font-medium"
                        >
                            New start time
                        </label>
                        <input
                            id={`trainer-worktime-start-${worktime.id}`}
                            type="time"
                            step={60}
                            className={`w-full rounded-md border px-3 py-2 outline-none ${
                                errors.startTime ? "border-primary-500" : "border-secondary-500"
                            }`}
                            aria-invalid={errors.startTime ? "true" : "false"}
                            aria-describedby={
                                errors.startTime
                                    ? `trainer-worktime-start-${worktime.id}-error`
                                    : undefined
                            }
                            {...register("startTime")}
                        />
                        {errors.startTime && (
                            <p
                                id={`trainer-worktime-start-${worktime.id}-error`}
                                className="mt-1 text-sm text-primary-500"
                                role="alert"
                            >
                                {errors.startTime.message}
                            </p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor={`trainer-worktime-end-${worktime.id}`}
                            className="mb-1 block text-sm font-medium"
                        >
                            New end time
                        </label>
                        <input
                            id={`trainer-worktime-end-${worktime.id}`}
                            type="time"
                            step={60}
                            className={`w-full rounded-md border px-3 py-2 outline-none ${
                                errors.endTime ? "border-primary-500" : "border-secondary-500"
                            }`}
                            aria-invalid={errors.endTime ? "true" : "false"}
                            aria-describedby={
                                errors.endTime
                                    ? `trainer-worktime-end-${worktime.id}-error`
                                    : undefined
                            }
                            {...register("endTime", {
                                validate: (value) => {
                                    const startTime = getValues("startTime");

                                    if (value === "" || startTime === "") {
                                        return true;
                                    }

                                    return value > startTime
                                        ? true
                                        : "End time must be later than start time.";
                                },
                            })}
                        />
                        {errors.endTime && (
                            <p
                                id={`trainer-worktime-end-${worktime.id}-error`}
                                className="mt-1 text-sm text-primary-500"
                                role="alert"
                            >
                                {errors.endTime.message}
                            </p>
                        )}
                    </div>
                </div>

                {errors.root?.server && (
                    <p className="mt-4 text-sm text-primary-500" role="alert">
                        {errors.root.server.message}
                    </p>
                )}

                {deleteError && (
                    <p className="mt-4 text-sm text-primary-500" role="alert">
                        {deleteError}
                    </p>
                )}

                <div className="mt-5 flex flex-wrap gap-3">
                    <button
                        type="submit"
                        disabled={isBusy}
                        className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {isUpdating ? "Updating..." : "Update worktime"}
                    </button>
                    <button
                        type="button"
                        disabled={isBusy}
                        className="rounded-md border border-gray-300 bg-white px-5 py-2 font-semibold transition hover:border-secondary-500 disabled:cursor-not-allowed disabled:opacity-50"
                        onClick={() => {
                            clearErrors();
                            reset();
                        }}
                    >
                        Clear
                    </button>
                    <button
                        type="button"
                        disabled={isBusy}
                        className="rounded-md bg-primary-300 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                        onClick={() => void handleDelete()}
                    >
                        {isDeleting ? "Deleting..." : "Delete worktime"}
                    </button>
                </div>
            </form>
        </article>
    );
});

export default TrainerWorktimeCard;
