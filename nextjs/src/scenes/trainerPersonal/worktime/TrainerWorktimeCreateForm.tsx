'use client';

import { ApiClientError } from "@/lib/apiClient";
import { notify } from "@/lib/notify";
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
        <section className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <h3 className="text-2xl font-bold">Create worktime</h3>
            <p className="mt-2 text-sm text-gray-600">
                The backend accepts one interval per trainer and date. Times are sent without browser timezone conversion.
            </p>

            <form
                className="mt-6 grid gap-5 md:grid-cols-3"
                onSubmit={handleSubmit(onSubmit)}
                noValidate
            >
                <div>
                    <label htmlFor="trainer-worktime-date" className="mb-1 block font-medium">
                        Date
                    </label>
                    <input
                        id="trainer-worktime-date"
                        type="date"
                        className={`w-full rounded-md border px-3 py-2 outline-none ${
                            errors.date ? "border-primary-500" : "border-secondary-500"
                        }`}
                        aria-invalid={errors.date ? "true" : "false"}
                        aria-describedby={errors.date ? "trainer-worktime-date-error" : undefined}
                        {...register("date", {
                            required: "Date is required.",
                        })}
                    />
                    {errors.date && (
                        <p
                            id="trainer-worktime-date-error"
                            className="mt-1 text-sm text-primary-500"
                            role="alert"
                        >
                            {errors.date.message}
                        </p>
                    )}
                </div>

                <div>
                    <label htmlFor="trainer-worktime-start" className="mb-1 block font-medium">
                        Start time
                    </label>
                    <input
                        id="trainer-worktime-start"
                        type="time"
                        step={60}
                        className={`w-full rounded-md border px-3 py-2 outline-none ${
                            errors.startTime ? "border-primary-500" : "border-secondary-500"
                        }`}
                        aria-invalid={errors.startTime ? "true" : "false"}
                        aria-describedby={errors.startTime ? "trainer-worktime-start-error" : undefined}
                        {...register("startTime", {
                            required: "Start time is required.",
                        })}
                    />
                    {errors.startTime && (
                        <p
                            id="trainer-worktime-start-error"
                            className="mt-1 text-sm text-primary-500"
                            role="alert"
                        >
                            {errors.startTime.message}
                        </p>
                    )}
                </div>

                <div>
                    <label htmlFor="trainer-worktime-end" className="mb-1 block font-medium">
                        End time
                    </label>
                    <input
                        id="trainer-worktime-end"
                        type="time"
                        step={60}
                        className={`w-full rounded-md border px-3 py-2 outline-none ${
                            errors.endTime ? "border-primary-500" : "border-secondary-500"
                        }`}
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
                        <p
                            id="trainer-worktime-end-error"
                            className="mt-1 text-sm text-primary-500"
                            role="alert"
                        >
                            {errors.endTime.message}
                        </p>
                    )}
                </div>

                {errors.root?.server && (
                    <p className="text-sm text-primary-500 md:col-span-3" role="alert">
                        {errors.root.server.message}
                    </p>
                )}

                <div className="flex flex-wrap gap-3 md:col-span-3">
                    <button
                        type="submit"
                        disabled={isBusy}
                        className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {trainerWorktimeStore.isCreating ? "Creating..." : "Create worktime"}
                    </button>
                    <button
                        type="button"
                        disabled={isBusy}
                        className="rounded-md border border-gray-300 bg-white px-5 py-2 font-semibold transition hover:border-secondary-500 disabled:cursor-not-allowed disabled:opacity-50"
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
