'use client';

import { ApiClientError } from "@/lib/apiClient";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { notify } from "@/lib/notify";
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
        <section className="rounded-2xl bg-white p-6 shadow-md sm:p-8">
            <h2 className="text-2xl font-bold">Profile photo</h2>
            <p className="mt-1 text-sm text-gray-500">
                JPEG, PNG, or WebP. Maximum file size: 2 MB.
            </p>

            <form
                className="mt-6 flex flex-col gap-4"
                onSubmit={handleSubmit(onSubmit)}
                noValidate
            >
                <div>
                    <label htmlFor="trainer-photo" className="mb-1 block font-medium">
                        Photo file
                    </label>
                    <input
                        id="trainer-photo"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        className="block w-full rounded-md border border-secondary-500 px-3 py-2 text-sm"
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
                    <p id="trainer-photo-help" className="mt-1 text-xs text-gray-500">
                        The backend also validates image dimensions and aspect ratio.
                    </p>
                    {errors.photo && (
                        <p
                            id="trainer-photo-error"
                            className="mt-1 text-sm text-primary-500"
                            role="alert"
                        >
                            {errors.photo.message}
                        </p>
                    )}
                </div>

                {errors.root?.server && (
                    <p className="text-sm text-primary-500" role="alert">
                        {errors.root.server.message}
                    </p>
                )}

                <button
                    type="submit"
                    disabled={isBusy}
                    className="self-start rounded-md bg-secondary-500 px-5 py-2 font-medium transition-colors hover:bg-primary-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {trainerStore.isUploading ? "Uploading..." : "Upload photo"}
                </button>
            </form>
        </section>
    );
});

export default TrainerPhotoForm;
