'use client';

import { ApiClientError } from "@/lib/apiClient";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { notify } from "@/lib/notify";
import Section, { primaryActionClassName } from "@/shared/Section";
import { useStore } from "@/store/StoreProvider";
import { observer } from "mobx-react-lite";
import { useForm } from "react-hook-form";

const MAX_PHOTO_SIZE_BYTES = 2 * 1024 * 1024;
const ACCEPTED_PHOTO_TYPES = new Set([
    "image/jpeg",
    "image/png",
    "image/webp",
]);

interface TrainerPhotoFormValues {
    photo: FileList;
}

const getFirstPhoto = (files: FileList | undefined): File | null => {
    return files?.item(0) ?? null;
};

const inputClassName = "block w-full rounded-md border border-gray-100 bg-gray-20 px-3 py-2 text-sm font-normal text-gray-500 transition file:mr-4 file:rounded-md file:border-0 file:bg-secondary-500 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-500 hover:file:bg-primary-500 hover:file:text-white focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:opacity-60";
const inputErrorClassName = "border-primary-500 focus:border-primary-500 focus:ring-primary-500/20";
const fieldClassName = "flex flex-col gap-2 text-sm font-semibold text-gray-500";

const TrainerPhotoForm = observer(() => {
    const { trainerStore } = useStore();

    const {
        register,
        handleSubmit,
        reset,
        setError,
        clearErrors,
        formState: {
            errors,
            isSubmitting,
        },
    } = useForm<TrainerPhotoFormValues>({
        mode: "onChange",
    });

    const onSubmit = async (
        values: TrainerPhotoFormValues,
    ): Promise<void> => {
        clearErrors("root.server");

        const photo = getFirstPhoto(values.photo);
        if (photo === null) {
            setError("photo", {
                type: "required",
                message: "Choose a photo to upload.",
            });
            return;
        }

        const toastId = notify.loading("Uploading trainer photo...");

        try {
            await trainerStore.uploadPhoto(photo);
            reset();
            notify.success(
                "Photo updated",
                "Your profile photo was uploaded and the profile was reloaded.",
                toastId,
            );
        } catch (error: unknown) {
            if (error instanceof ApiClientError && error.status === 422) {
                const violation = error.payload.violations?.find(
                    (item) => item.propertyPath === "photo",
                );

                if (violation !== undefined) {
                    setError("photo", {
                        type: "server",
                        message: violation.title || violation.message || "Invalid photo.",
                    });
                    notify.dismiss(toastId);
                    return;
                }
            }

            setError("root.server", {
                type: "server",
                message: getErrorMessage(error, "Failed to upload the trainer photo."),
            });
            notify.dismiss(toastId);
        }
    };

    const isBusy = isSubmitting || trainerStore.isMutating;

    return (
        <Section
            title="Profile photo"
            description="JPEG, PNG, or WebP. Maximum file size: 2 MB."
            className="h-full"
        >
            <form
                className="flex flex-col gap-4"
                onSubmit={handleSubmit(onSubmit)}
                noValidate
            >
                <label htmlFor="trainer-photo" className={fieldClassName}>
                    Photo file
                    <input
                        id="trainer-photo"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        className={`${inputClassName} ${errors.photo ? inputErrorClassName : ""}`}
                        disabled={isBusy}
                        aria-invalid={errors.photo ? "true" : "false"}
                        aria-describedby={
                            errors.photo
                                ? "trainer-photo-error"
                                : "trainer-photo-help"
                        }
                        {...register("photo", {
                            required: "Choose a photo to upload.",
                            validate: {
                                mimeType: (files) => {
                                    const file = getFirstPhoto(files);

                                    return file === null
                                        || ACCEPTED_PHOTO_TYPES.has(file.type)
                                        || "Use a JPEG, PNG, or WebP image.";
                                },
                                size: (files) => {
                                    const file = getFirstPhoto(files);

                                    return file === null
                                        || file.size <= MAX_PHOTO_SIZE_BYTES
                                        || "Photo must not exceed 2 MB.";
                                },
                            },
                        })}
                    />
                    <span id="trainer-photo-help" className="font-normal text-gray-500">
                        The backend also validates image dimensions and aspect ratio.
                    </span>
                    {errors.photo && (
                        <span
                            id="trainer-photo-error"
                            className="font-normal text-primary-500"
                            role="alert"
                        >
                            {errors.photo.message}
                        </span>
                    )}
                </label>

                {errors.root?.server && (
                    <p className="text-sm text-primary-500" role="alert">
                        {errors.root.server.message}
                    </p>
                )}

                <button
                    type="submit"
                    disabled={isBusy}
                    className={`self-start ${primaryActionClassName}`}
                >
                    {trainerStore.isUploading ? "Uploading..." : "Upload photo"}
                </button>
            </form>
        </Section>
    );
});

export default TrainerPhotoForm;
