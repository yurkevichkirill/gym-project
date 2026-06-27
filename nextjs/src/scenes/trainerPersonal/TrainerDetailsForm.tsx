'use client';

import { ApiClientError } from "@/lib/apiClient";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { notify } from "@/lib/notify";
import { useStore } from "@/store/StoreProvider";
import TrainerEditType from "@/types/trainer/private/trainer-edit.type";
import { TrainerPersonalType } from "@/types/trainer/private/trainer.personal.type";
import { observer } from "mobx-react-lite";
import { useEffect } from "react";
import { useForm } from "react-hook-form";

interface TrainerProfileFormValues {
    phone: string;
    pricePerHour: number;
}

const isProfileField = (
    propertyPath: unknown,
): propertyPath is keyof TrainerProfileFormValues => {
    return propertyPath === "phone" || propertyPath === "pricePerHour";
};

const TrainerDetailsForm = observer(({
    trainer,
}: {
    trainer: TrainerPersonalType;
}) => {
    const { trainerStore } = useStore();

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
    } = useForm<TrainerProfileFormValues>({
        mode: "onBlur",
        defaultValues: {
            phone: trainer.phone,
            pricePerHour: trainer.pricePerHour,
        },
    });

    useEffect(() => {
        reset({
            phone: trainer.phone,
            pricePerHour: trainer.pricePerHour,
        });
    }, [reset, trainer.phone, trainer.pricePerHour]);

    const onSubmit = async (values: TrainerProfileFormValues): Promise<void> => {
        clearErrors("root.server");

        const phone = values.phone.trim();
        const payload: TrainerEditType = {};

        if (phone !== trainer.phone) {
            payload.phone = phone;
        }

        if (values.pricePerHour !== trainer.pricePerHour) {
            payload.pricePerHour = values.pricePerHour;
        }

        if (Object.keys(payload).length === 0) {
            reset({
                phone: trainer.phone,
                pricePerHour: trainer.pricePerHour,
            });
            return;
        }

        const toastId = notify.loading("Updating trainer profile...");

        try {
            await trainerStore.update(payload);
            notify.success(
                "Profile updated",
                "Your trainer profile was reloaded with the saved values.",
                toastId,
            );
        } catch (error: unknown) {
            let fieldErrorWasApplied = false;

            if (error instanceof ApiClientError && error.status === 422) {
                for (const violation of error.payload.violations ?? []) {
                    if (!isProfileField(violation.propertyPath)) {
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

            setError("root.server", {
                type: "server",
                message: getErrorMessage(error, "Failed to update the trainer profile."),
            });
            notify.dismiss(toastId);
        }
    };

    const isBusy = isSubmitting || trainerStore.isMutating;

    return (
        <section className="rounded-2xl bg-white p-6 shadow-md sm:p-8">
            <h2 className="text-2xl font-bold">Edit profile</h2>
            <p className="mt-1 text-sm text-gray-500">
                Only fields supported by the trainer update API can be changed.
            </p>

            <form
                className="mt-6 flex flex-col gap-5"
                onSubmit={handleSubmit(onSubmit)}
                noValidate
            >
                <div>
                    <label htmlFor="trainer-phone" className="mb-1 block font-medium">
                        Phone
                    </label>
                    <input
                        id="trainer-phone"
                        type="tel"
                        autoComplete="tel"
                        className={`w-full rounded-md border px-3 py-2 outline-none ${
                            errors.phone
                                ? "border-primary-500"
                                : "border-secondary-500"
                        }`}
                        aria-invalid={errors.phone ? "true" : "false"}
                        aria-describedby={errors.phone ? "trainer-phone-error" : undefined}
                        {...register("phone", {
                            required: "Phone is required.",
                            pattern: {
                                value: /^\+?[1-9]\d{4,14}$/,
                                message: "Use international format, for example +123456789.",
                            },
                        })}
                    />
                    {errors.phone && (
                        <p
                            id="trainer-phone-error"
                            className="mt-1 text-sm text-primary-500"
                            role="alert"
                        >
                            {errors.phone.message}
                        </p>
                    )}
                </div>

                <div>
                    <label
                        htmlFor="trainer-price-per-hour"
                        className="mb-1 block font-medium"
                    >
                        Price per hour, cents
                    </label>
                    <input
                        id="trainer-price-per-hour"
                        type="number"
                        inputMode="numeric"
                        min={1}
                        step={1}
                        className={`w-full rounded-md border px-3 py-2 outline-none ${
                            errors.pricePerHour
                                ? "border-primary-500"
                                : "border-secondary-500"
                        }`}
                        aria-invalid={errors.pricePerHour ? "true" : "false"}
                        aria-describedby={
                            errors.pricePerHour
                                ? "trainer-price-per-hour-error"
                                : undefined
                        }
                        {...register("pricePerHour", {
                            required: "Price per hour is required.",
                            valueAsNumber: true,
                            validate: (value) => (
                                Number.isInteger(value) && value > 0
                                    ? true
                                    : "Price per hour must be a positive integer."
                            ),
                        })}
                    />
                    {errors.pricePerHour && (
                        <p
                            id="trainer-price-per-hour-error"
                            className="mt-1 text-sm text-primary-500"
                            role="alert"
                        >
                            {errors.pricePerHour.message}
                        </p>
                    )}
                </div>

                {errors.root?.server && (
                    <p className="text-sm text-primary-500" role="alert">
                        {errors.root.server.message}
                    </p>
                )}

                <div className="flex flex-col gap-3 sm:flex-row">
                    <button
                        type="submit"
                        disabled={isBusy || !isDirty}
                        className="rounded-md bg-secondary-500 px-5 py-2 font-medium transition-colors hover:bg-primary-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {trainerStore.isUpdating ? "Saving..." : "Save changes"}
                    </button>
                    <button
                        type="button"
                        disabled={isBusy || !isDirty}
                        className="rounded-md bg-gray-100 px-5 py-2 font-medium transition-colors hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50"
                        onClick={() => reset({
                            phone: trainer.phone,
                            pricePerHour: trainer.pricePerHour,
                        })}
                    >
                        Reset
                    </button>
                </div>
            </form>
        </section>
    );
});

export default TrainerDetailsForm;
