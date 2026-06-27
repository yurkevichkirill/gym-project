'use client'

import { useEffect, useMemo, useState } from "react";
import { observer } from "mobx-react-lite";
import { useForm } from "react-hook-form";
import { ApiClientError } from "@/lib/apiClient";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import BookingCreateType from "@/types/booking/booking-create.type";
import TrainingTypeData from "@/types/training-type.type";
import TrainerData from "@/types/trainer/public/trainer.type";
import WorktimeData from "@/types/trainer/public/worktime.type";
import FreeSlotData from "@/types/trainer/public/free-slot.type";
import { getTrainingTypesPage } from "@/api/public/training-types.api";
import { getTrainers } from "@/api/public/trainers.api";
import { getWorktimesPage } from "@/api/public/worktime.api";
import { createStripeIntent } from "@/api/client/payments.api";
import { PaymentMethodEnum } from "@/types/payment/payment-method.enum";
import { generateDurationMinutes, generateStartTimes } from "@/lib/utils/time.utils";
import { useStore } from "@/store/StoreProvider";
import { notify } from "@/lib/notify";
import { StripeModal } from "@/scenes/stripe/stripeModal";
import { getBookingMutationErrorMessage } from "@/scenes/clientPersonal/bookings/booking-mutation-error";

const PAGE_SIZE = 100;

type OptionsState<T> = {
    items: T[];
    isLoading: boolean;
    error: string | null;
};

const emptyOptionsState = <T,>(): OptionsState<T> => ({
    items: [],
    isLoading: false,
    error: null,
});

const isAbortError = (error: unknown): boolean => {
    return error instanceof Error && error.name === "AbortError";
};

const loadAllPages = async <T,>(
    loadPage: (page: number) => Promise<ApiCollectionResponse<T[]>>,
): Promise<T[]> => {
    const firstPage = await loadPage(1);
    const pageCount = firstPage.meta.pagination.pages;

    if (pageCount <= 1) {
        return firstPage.data;
    }

    const remainingPages = await Promise.all(
        Array.from({ length: pageCount - 1 }, (_, index) => loadPage(index + 2)),
    );

    return [
        ...firstPage.data,
        ...remainingPages.flatMap((response) => response.data),
    ];
};

const getSlotKey = (slot: FreeSlotData): string => `${slot.start}-${slot.end}`;

const BookingCreateForm = observer(() => {
    const { bookingStore } = useStore();
    const [trainingTypesState, setTrainingTypesState] = useState<OptionsState<TrainingTypeData>>({
        items: [],
        isLoading: true,
        error: null,
    });
    const [trainersState, setTrainersState] = useState<OptionsState<TrainerData>>(
        emptyOptionsState,
    );
    const [worktimesState, setWorktimesState] = useState<OptionsState<WorktimeData>>(
        emptyOptionsState,
    );
    const [selectedTrainingTypeId, setSelectedTrainingTypeId] = useState<number | null>(null);
    const [selectedWorktimeId, setSelectedWorktimeId] = useState<number | null>(null);
    const [selectedSlotKey, setSelectedSlotKey] = useState<string | null>(null);
    const [worktimesReloadVersion, setWorktimesReloadVersion] = useState(0);
    const [stripeClientSecret, setStripeClientSecret] = useState<string | null>(null);
    const [paymentBookingId, setPaymentBookingId] = useState<number | null>(null);

    const {
        register,
        handleSubmit,
        watch,
        setValue,
        setError,
        clearErrors,
        reset,
        formState: { errors, isSubmitting },
    } = useForm<BookingCreateType>({
        defaultValues: {
            trainerId: 0,
            date: "",
            startTime: "",
            durationMinutes: 0,
        },
    });

    const trainerId = watch("trainerId");
    const selectedDate = watch("date");
    const selectedStartTime = watch("startTime");
    const selectedDurationMinutes = watch("durationMinutes");

    useEffect(() => {
        const controller = new AbortController();
        let isCurrent = true;

        void loadAllPages((page) => getTrainingTypesPage(
            { sort: "name:ASC", page, limit: PAGE_SIZE },
            { signal: controller.signal },
        ))
            .then((items) => {
                if (!isCurrent) {
                    return;
                }

                setTrainingTypesState({
                    items,
                    isLoading: false,
                    error: null,
                });
            })
            .catch((error: unknown) => {
                if (isAbortError(error) || !isCurrent) {
                    return;
                }

                setTrainingTypesState({
                    items: [],
                    isLoading: false,
                    error: getBookingMutationErrorMessage(
                        error,
                        "Unable to load training types.",
                    ),
                });
            });

        return () => {
            isCurrent = false;
            controller.abort();
        };
    }, []);

    useEffect(() => {
        if (selectedTrainingTypeId === null) {
            return;
        }

        const controller = new AbortController();
        let isCurrent = true;

        void loadAllPages((page) => getTrainers(
            {
                trainingTypeId: selectedTrainingTypeId,
                sort: "lastName:ASC",
                page,
                limit: PAGE_SIZE,
            },
            { signal: controller.signal },
        ))
            .then((items) => {
                if (!isCurrent) {
                    return;
                }

                setTrainersState({
                    items,
                    isLoading: false,
                    error: null,
                });
            })
            .catch((error: unknown) => {
                if (isAbortError(error) || !isCurrent) {
                    return;
                }

                setTrainersState({
                    items: [],
                    isLoading: false,
                    error: getBookingMutationErrorMessage(error, "Unable to load trainers."),
                });
            });

        return () => {
            isCurrent = false;
            controller.abort();
        };
    }, [selectedTrainingTypeId]);

    useEffect(() => {
        if (!Number.isSafeInteger(trainerId) || trainerId <= 0) {
            return;
        }

        const controller = new AbortController();
        let isCurrent = true;

        void loadAllPages((page) => getWorktimesPage(
            {
                trainerId,
                sort: "date:ASC",
                page,
                limit: PAGE_SIZE,
            },
            { signal: controller.signal },
        ))
            .then((items) => {
                if (!isCurrent) {
                    return;
                }

                setWorktimesState({
                    items,
                    isLoading: false,
                    error: null,
                });
            })
            .catch((error: unknown) => {
                if (isAbortError(error) || !isCurrent) {
                    return;
                }

                setWorktimesState({
                    items: [],
                    isLoading: false,
                    error: getBookingMutationErrorMessage(error, "Unable to load worktimes."),
                });
            });

        return () => {
            isCurrent = false;
            controller.abort();
        };
    }, [
        bookingStore.availabilityRevision,
        trainerId,
        worktimesReloadVersion,
    ]);

    const selectedTrainer = useMemo(
        () => trainersState.items.find((trainer) => trainer.id === trainerId) ?? null,
        [trainerId, trainersState.items],
    );
    const selectedWorktime = useMemo(
        () => worktimesState.items.find((worktime) => worktime.id === selectedWorktimeId) ?? null,
        [selectedWorktimeId, worktimesState.items],
    );
    const selectedSlot = useMemo(
        () => selectedWorktime?.freeSlots.find((slot) => getSlotKey(slot) === selectedSlotKey) ?? null,
        [selectedSlotKey, selectedWorktime],
    );
    const startTimes = useMemo(
        () => selectedSlot ? generateStartTimes(selectedSlot.start, selectedSlot.end) : [],
        [selectedSlot],
    );
    const durationOptions = useMemo(
        () => selectedSlot && selectedStartTime
            ? generateDurationMinutes(selectedSlot.end, selectedStartTime)
            : new Map<number, string>(),
        [selectedSlot, selectedStartTime],
    );
    const formattedPrice = useMemo(() => {
        if (!selectedTrainer || !selectedDurationMinutes) {
            return null;
        }

        return new Intl.NumberFormat("en-US", {
            style: "currency",
            currency: "USD",
        }).format(
            ((selectedTrainer.pricePerHour * selectedDurationMinutes) / 60) / 100,
        );
    }, [selectedDurationMinutes, selectedTrainer]);

    const clearSchedule = () => {
        setSelectedWorktimeId(null);
        setSelectedSlotKey(null);
        setValue("date", "");
        setValue("startTime", "");
        setValue("durationMinutes", 0);
        clearErrors(["date", "startTime", "durationMinutes"]);
    };

    const applyValidationViolations = (error: unknown): void => {
        if (!(error instanceof ApiClientError) || error.status !== 422) {
            return;
        }

        error.payload.violations?.forEach((violation) => {
            const message = violation.message ?? violation.title ?? "Invalid value.";

            switch (violation.propertyPath) {
                case "trainerId":
                    setError("trainerId", { type: "server", message });
                    break;
                case "date":
                    setError("date", { type: "server", message });
                    break;
                case "startTime":
                    setError("startTime", { type: "server", message });
                    break;
                case "durationMinutes":
                    setError("durationMinutes", { type: "server", message });
                    break;
            }
        });
    };

    const submitBooking = async (values: BookingCreateType) => {
        clearErrors("root");
        const toastId = notify.loading("Creating booking...");

        try {
            const booking = await bookingStore.book(values);

            reset({
                trainerId: booking.trainerId,
                date: "",
                startTime: "",
                durationMinutes: 0,
            });
            setSelectedWorktimeId(null);
            setSelectedSlotKey(null);

            if (booking.payment.method === PaymentMethodEnum.CARD) {
                setPaymentBookingId(booking.id);

                try {
                    const clientSecret = await createStripeIntent(booking.payment.id);
                    notify.dismiss(toastId);
                    setStripeClientSecret(clientSecret);
                } catch (error: unknown) {
                    const message = getBookingMutationErrorMessage(
                        error,
                        `Booking #${booking.id} was created, but card payment could not be opened.`,
                    );

                    setError("root.server", { type: "server", message });
                    notify.error(
                        "Booking created, payment unavailable",
                        `${message} Open the pending booking from your history before trying again.`,
                        toastId,
                    );
                }

                return;
            }

            notify.success(
                "Booking created",
                `${booking.durationMinutes} minutes on ${booking.date} were paid from your balance.`,
                toastId,
            );
        } catch (error: unknown) {
            applyValidationViolations(error);

            if (error instanceof ApiClientError && error.status === 409) {
                clearSchedule();
                setWorktimesState({
                    items: [],
                    isLoading: true,
                    error: null,
                });
                setWorktimesReloadVersion((version) => version + 1);
            }

            const message = getBookingMutationErrorMessage(
                error,
                "Unable to create this booking.",
            );

            setError("root.server", { type: "server", message });
            notify.error("Booking failed", message, toastId);
        }
    };

    const isBusy = isSubmitting || bookingStore.isCreating;

    return (
        <>
            <section className="mb-8 rounded-2xl border border-secondary-100 bg-white p-5 shadow-sm sm:p-6">
                <div className="mb-6">
                    <p className="text-sm font-semibold uppercase tracking-wider text-secondary-500">
                        New booking
                    </p>
                    <h2 className="mt-1 text-2xl font-bold">Book a training session</h2>
                    <p className="mt-2 text-sm text-gray-600">
                        Training type, trainer, worktime, and free intervals come from the existing APIs.
                        The submitted payload contains only the BookingRequestDTO fields.
                    </p>
                </div>

                <form onSubmit={handleSubmit(submitBooking)} noValidate>
                    <fieldset disabled={isBusy} className="grid gap-5 disabled:opacity-70 lg:grid-cols-2">
                        <label className="flex flex-col gap-2 text-sm font-semibold">
                            Training type
                            <select
                                value={selectedTrainingTypeId ?? ""}
                                className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none"
                                onChange={(event) => {
                                    const value = Number(event.target.value);
                                    const nextTrainingTypeId = Number.isSafeInteger(value) && value > 0
                                        ? value
                                        : null;

                                    setSelectedTrainingTypeId(nextTrainingTypeId);
                                    setTrainersState(nextTrainingTypeId === null
                                        ? emptyOptionsState<TrainerData>()
                                        : { items: [], isLoading: true, error: null });
                                    setWorktimesState(emptyOptionsState<WorktimeData>());
                                    setValue("trainerId", 0);
                                    clearErrors("trainerId");
                                    clearSchedule();
                                }}
                            >
                                <option value="">
                                    {trainingTypesState.isLoading ? "Loading training types..." : "Select training type"}
                                </option>
                                {trainingTypesState.items.map((trainingType) => (
                                    <option key={trainingType.id} value={trainingType.id}>
                                        {trainingType.name}
                                    </option>
                                ))}
                            </select>
                            {trainingTypesState.error ? (
                                <span className="font-normal text-red-600">{trainingTypesState.error}</span>
                            ) : null}
                        </label>

                        <label className="flex flex-col gap-2 text-sm font-semibold">
                            Trainer
                            <select
                                className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none"
                                disabled={selectedTrainingTypeId === null || trainersState.isLoading || isBusy}
                                {...register("trainerId", {
                                    valueAsNumber: true,
                                    validate: (value) => value > 0 || "Select a trainer.",
                                    onChange: (event) => {
                                        const nextTrainerId = Number(event.target.value);

                                        setWorktimesState(
                                            Number.isSafeInteger(nextTrainerId) && nextTrainerId > 0
                                                ? { items: [], isLoading: true, error: null }
                                                : emptyOptionsState<WorktimeData>(),
                                        );
                                        clearSchedule();
                                    },
                                })}
                            >
                                <option value={0}>
                                    {trainersState.isLoading ? "Loading trainers..." : "Select trainer"}
                                </option>
                                {trainersState.items.map((trainer) => (
                                    <option key={trainer.id} value={trainer.id}>
                                        {trainer.firstName} {trainer.lastName} · {trainer.trainingType.name}
                                    </option>
                                ))}
                            </select>
                            {errors.trainerId ? (
                                <span className="font-normal text-red-600">{errors.trainerId.message}</span>
                            ) : null}
                            {trainersState.error ? (
                                <span className="font-normal text-red-600">{trainersState.error}</span>
                            ) : null}
                        </label>

                        <label className="flex flex-col gap-2 text-sm font-semibold lg:col-span-2">
                            Worktime and date
                            <select
                                value={selectedWorktimeId ?? ""}
                                className="rounded-md border border-gray-300 px-3 py-2 font-normal focus:border-secondary-500 focus:outline-none"
                                disabled={!trainerId || worktimesState.isLoading || isBusy}
                                onChange={(event) => {
                                    const id = Number(event.target.value);
                                    const worktime = worktimesState.items.find((item) => item.id === id) ?? null;

                                    setSelectedWorktimeId(worktime?.id ?? null);
                                    setSelectedSlotKey(null);
                                    setValue("date", worktime?.date ?? "", { shouldValidate: true });
                                    setValue("startTime", "");
                                    setValue("durationMinutes", 0);
                                    clearErrors(["startTime", "durationMinutes"]);
                                }}
                            >
                                <option value="">
                                    {worktimesState.isLoading ? "Loading worktimes..." : "Select worktime"}
                                </option>
                                {worktimesState.items.map((worktime) => (
                                    <option key={worktime.id} value={worktime.id}>
                                        {worktime.date} · worktime #{worktime.id} · {worktime.freeSlots.length} free interval{worktime.freeSlots.length === 1 ? "" : "s"}
                                    </option>
                                ))}
                            </select>
                            <input
                                type="hidden"
                                {...register("date", {
                                    required: "Select a worktime returned by the API.",
                                })}
                            />
                            {errors.date ? (
                                <span className="font-normal text-red-600">{errors.date.message}</span>
                            ) : null}
                            {worktimesState.error ? (
                                <span className="font-normal text-red-600">{worktimesState.error}</span>
                            ) : null}
                            {!worktimesState.isLoading && trainerId > 0 && worktimesState.items.length === 0 ? (
                                <span className="font-normal text-gray-500">
                                    No available worktimes were returned for this trainer.
                                </span>
                            ) : null}
                        </label>

                        {selectedWorktime ? (
                            <div className="lg:col-span-2">
                                <p className="text-sm font-semibold">Free interval</p>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {selectedWorktime.freeSlots.map((slot) => {
                                        const slotKey = getSlotKey(slot);

                                        return (
                                            <button
                                                key={slotKey}
                                                type="button"
                                                className={`rounded-md px-3 py-2 text-sm font-semibold transition ${
                                                    selectedSlotKey === slotKey
                                                        ? "bg-primary-500 text-white"
                                                        : "bg-primary-100 hover:bg-primary-200"
                                                }`}
                                                onClick={() => {
                                                    setSelectedSlotKey(slotKey);
                                                    setValue("startTime", "");
                                                    setValue("durationMinutes", 0);
                                                    clearErrors(["startTime", "durationMinutes"]);
                                                }}
                                            >
                                                {slot.start.slice(0, 5)}–{slot.end.slice(0, 5)}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        ) : null}

                        {selectedSlot ? (
                            <div>
                                <p className="text-sm font-semibold">Start time</p>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {startTimes.map((time) => (
                                        <button
                                            key={time}
                                            type="button"
                                            className={`rounded-md px-3 py-2 text-sm font-semibold transition ${
                                                selectedStartTime === `${time}:00`
                                                    ? "bg-primary-500 text-white"
                                                    : "bg-primary-100 hover:bg-primary-200"
                                            }`}
                                            onClick={() => {
                                                setValue("startTime", `${time}:00`, { shouldValidate: true });
                                                setValue("durationMinutes", 0);
                                                clearErrors("durationMinutes");
                                            }}
                                        >
                                            {time}
                                        </button>
                                    ))}
                                </div>
                                <input
                                    type="hidden"
                                    {...register("startTime", {
                                        required: "Select a start time.",
                                    })}
                                />
                                {errors.startTime ? (
                                    <span className="mt-2 block text-sm text-red-600">{errors.startTime.message}</span>
                                ) : null}
                            </div>
                        ) : null}

                        {selectedSlot && selectedStartTime ? (
                            <div>
                                <p className="text-sm font-semibold">Duration</p>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {Array.from(durationOptions.keys()).map((duration) => (
                                        <button
                                            key={duration}
                                            type="button"
                                            className={`rounded-md px-3 py-2 text-sm font-semibold transition ${
                                                selectedDurationMinutes === duration
                                                    ? "bg-primary-500 text-white"
                                                    : "bg-primary-100 hover:bg-primary-200"
                                            }`}
                                            onClick={() => {
                                                setValue("durationMinutes", duration, { shouldValidate: true });
                                            }}
                                        >
                                            {duration} min
                                        </button>
                                    ))}
                                </div>
                                <input
                                    type="hidden"
                                    {...register("durationMinutes", {
                                        valueAsNumber: true,
                                        validate: (value) => (
                                            Number.isInteger(value)
                                            && value >= 30
                                            && value <= 1440
                                            && value % 30 === 0
                                        ) || "Select a valid duration.",
                                    })}
                                />
                                {errors.durationMinutes ? (
                                    <span className="mt-2 block text-sm text-red-600">{errors.durationMinutes.message}</span>
                                ) : null}
                            </div>
                        ) : null}
                    </fieldset>

                    {selectedDate && selectedStartTime && selectedDurationMinutes > 0 ? (
                        <div className="mt-6 rounded-xl bg-primary-50 p-4 text-sm">
                            <p className="font-semibold">
                                {selectedTrainer?.firstName} {selectedTrainer?.lastName} · {selectedDate} at {selectedStartTime.slice(0, 5)}
                            </p>
                            <p className="mt-1 text-gray-600">
                                {selectedDurationMinutes} minutes{formattedPrice ? ` · ${formattedPrice}` : ""}
                            </p>
                        </div>
                    ) : null}

                    {errors.root?.server ? (
                        <div role="alert" className="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-700">
                            {errors.root.server.message}
                        </div>
                    ) : null}

                    <div className="mt-6 flex flex-wrap items-center gap-4">
                        <button
                            type="submit"
                            disabled={isBusy}
                            className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {isBusy ? "Creating booking..." : "Create booking"}
                        </button>
                        <p className="text-xs text-gray-500">
                            Displayed intervals are advisory. The backend performs the final availability,
                            membership, ownership, payment, and conflict checks.
                        </p>
                    </div>
                </form>
            </section>

            {stripeClientSecret ? (
                <StripeModal
                    clientSecret={stripeClientSecret}
                    onClose={() => {
                        setStripeClientSecret(null);

                        if (paymentBookingId !== null) {
                            void bookingStore.refreshAfterPayment(paymentBookingId);
                        }
                    }}
                    onSuccess={() => {
                        setStripeClientSecret(null);

                        if (paymentBookingId !== null) {
                            void bookingStore.refreshAfterPayment(paymentBookingId);
                        }
                    }}
                    successTitle="Training booked!"
                    successDescription="Your payment succeeded and the latest booking data is being refreshed."
                />
            ) : null}
        </>
    );
});

export default BookingCreateForm;
