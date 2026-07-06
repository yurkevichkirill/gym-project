'use client';

import { ApiClientError } from "@/lib/apiClient";
import { notify } from "@/lib/notify";
import { primaryActionClassName, previewCardClassName, secondaryActionClassName } from "@/shared/Section";
import { getTrainerWorktimeMutationErrorMessage } from "@/scenes/trainerPersonal/worktime/worktime-mutation-error";
import { useStore } from "@/store/StoreProvider";
import { TrainerWorktimeCreatePayload } from "@/types/trainer/private/trainer-worktime.type";
import { observer } from "mobx-react-lite";
import { useForm } from "react-hook-form";

interface CreateWorktimeFormValues {
    date: string;
    startTime: string;
    endTime: string;
}

const isCreateField = (
    propertyPath: unknown,
): propertyPath is keyof CreateWorktimeFormValues => {
    return propertyPath === "date"
        || propertyPath === "startTime"
        || propertyPath === "endTime";
};

const inputClassName = "w-full rounded-md border border-gray-100 bg-gray-20 px-3 py-2 font-normal text-gray-500 transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:opacity-60";
const inputErrorClassName = "border-primary-500 focus:border-primary-500 focus:ring-primary-500/20";
const fieldClassName = "flex flex-col gap-2 text-sm font-semibold text-gray-500";

const TrainerWorktimeCreateForm = observer(() => {
    const { trainerWorktimeStore } = useStore();
    const {
        register,
        handleSubmit,
        reset,
        setError,
        clearErrors,
        getValues,
        formState: { errors, isSubmitting },
    } = useForm<CreateWorktimeFormValues>({
        mode: "onBlur",
        defaultValues: {
            date: "",
            startTime: "",
            endTime: "",
        },
    });

    const onSubmit = async (values: CreateWorktimeFormValues): Promise<void> => {
        clearErrors();

        const payload: TrainerWorktimeCreatePayload = {
            date: values.date,
            startTime: values.startTime,
            endTime: values.endTime,
        };
        const toastId = notify.loading("Creating trainer worktime...");

        try {
            await trainerWorktimeStore.create(payload);
            reset();
            notify.success(
                "Worktime created",
                "The trainer worktime list was reloaded from the server.",
                toastId,
            );
        } catch (error: unknown) {
            let fieldErrorWasApplied = false;

            if (error instanceof ApiClientError && error.status === 422) {
                for (const violation of error.payload.violations ?? []) {
                    if (!isCreateField(violation.propertyPath)) {
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
                "Failed to create the trainer worktime.",
            );

            setError("root.server", {
                type: "server",
                message,
            });
            notify.error("Worktime creation failed", message, toastId);
        }
    };

    const isBusy = isSubmitting || trainerWorktimeStore.isMutating;

    return (
        <section className={previewCardClassName}>
            <h3 className="text-xl font-bold text-gray-500">Create worktime</h3>
            <p className="mt-2 text-sm text-gray-500">
                The backend accepts one interval per trainer and date. Times are sent without browser timezone conversion.
            </p>

            <form
                className="mt-6 grid gap-5 md:grid-cols-3"
                onSubmit={handleSubmit(onSubmit)}
                noValidate
            >
                <label htmlFor="trainer-worktime-date" className={fieldClassName}>
                    Date
                    <input
                        id="trainer-worktime-date"
                        type="date"
                        className={`${inputClassName} ${errors.date ? inputErrorClassName : ""}`}
                        disabled={isBusy}
                        aria-invalid={errors.date ? "true" : "false"}
                        aria-describedby={errors.date ? "trainer-worktime-date-error" : undefined}
                        {...register("date", {
                            required: "Date is required.",
                        })}
                    />
                    {errors.date && (
                        <span
                            id="trainer-worktime-date-error"
                            className="font-normal text-primary-500"
                            role="alert"
                        >
                            {errors.date.message}
                        </span>
                    )}
                </label>

                <label htmlFor="trainer-worktime-start" className={fieldClassName}>
                    Start time
                    <input
                        id="trainer-worktime-start"
                        type="time"
                        step={60}
                        className={`${inputClassName} ${errors.startTime ? inputErrorClassName : ""}`}
                        disabled={isBusy}
                        aria-invalid={errors.startTime ? "true" : "false"}
                        aria-describedby={errors.startTime ? "trainer-worktime-start-error" : undefined}
                        {...register("startTime", {
                            required: "Start time is required.",
                        })}
                    />
                    {errors.startTime && (
                        <span
                            id="trainer-worktime-start-error"
                            className="font-normal text-primary-500"
                            role="alert"
                        >
                            {errors.startTime.message}
                        </span>
                    )}
                </label>

                <label htmlFor="trainer-worktime-end" className={fieldClassName}>
                    End time
                    <input
                        id="trainer-worktime-end"
                        type="time"
                        step={60}
                        className={`${inputClassName} ${errors.endTime ? inputErrorClassName : ""}`}
                        disabled={isBusy}
                        aria-invalid={errors.endTime ? "true" : "false"}
                        aria-describedby={errors.endTime ? "trainer-worktime-end-error" : undefined}
                        {...register("endTime", {
                            required: "End time is required.",
                            validate: (value) => (
                                value > getValues("startTime")
                                    ? true
                                    : "End time must be later than start time."
                            ),
                        })}
                    />
                    {errors.endTime && (
                        <span
                            id="trainer-worktime-end-error"
                            className="font-normal text-primary-500"
                            role="alert"
                        >
                            {errors.endTime.message}
                        </span>
                    )}
                </label>

                {errors.root?.server && (
                    <p className="text-sm text-primary-500 md:col-span-3" role="alert">
                        {errors.root.server.message}
                    </p>
                )}

                <div className="flex flex-wrap gap-3 md:col-span-3">
                    <button
                        type="submit"
                        disabled={isBusy}
                        className={primaryActionClassName}
                    >
                        {trainerWorktimeStore.isCreating ? "Creating..." : "Create worktime"}
                    </button>
                    <button
                        type="button"
                        disabled={isBusy}
                        className={secondaryActionClassName}
                        onClick={() => reset()}
                    >
                        Reset
                    </button>
                </div>
            </form>
        </section>
    );
});

export default TrainerWorktimeCreateForm;
