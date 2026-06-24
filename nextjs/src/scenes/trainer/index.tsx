'use client'

import { SelectedPage } from "@/shared/types";
import { motion } from "framer-motion";
import type TrainerData from "@/types/trainer/public/trainer.type";
import type WorktimeData from "@/types/trainer/public/worktime.type";
import Worktime from "@/scenes/worktime/Worktime";
import { useNavigation } from "@/context/navigation-context";
import Image from "next/image";
import { notify } from "@/lib/notify";
import { useStore } from "@/store/StoreProvider";
import { useBooking } from "@/context/booking.context";
import { useCallback, useRef, useState } from "react";
import { PaymentMethodEnum } from "@/types/payment/payment-method.enum";
import { StripeModal } from "../stripe/stripeModal";
import { createStripeIntent } from "@/api/client/payments.api";
import { getWorktimes } from "@/api/public/worktime.api";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { resolveStorageUrl } from "@/lib/resolveStorageUrl";

type Props = {
    id: string;
    initialTrainer: TrainerData;
    initialWorktimes: WorktimeData[];
};

const TrainerPersonal = ({ id, initialTrainer, initialWorktimes }: Props) => {
    const { setSelectedPage } = useNavigation();
    const { booking } = useBooking();
    const { bookingStore, paymentStore } = useStore();
    const [worktimes, setWorktimes] = useState(initialWorktimes);
    const [stripeClientSecret, setStripeClientSecret] = useState<string | null>(null);
    const [isBooking, setIsBooking] = useState(false);
    const bookingRequestInFlight = useRef(false);

    const refreshWorktimes = useCallback(async () => {
        try {
            const refreshedWorktimes = await getWorktimes({
                trainerId: Number(id),
            });
            setWorktimes(refreshedWorktimes);
        } catch (error: unknown) {
            notify.error(
                "Availability refresh failed",
                getErrorMessage(error, "Reload the page to see current time slots."),
            );
        }
    }, [id]);

    const handleBooking = async () => {
        if (bookingRequestInFlight.current) {
            return;
        }

        if (!id || !booking.date || !booking.durationMinutes || !booking.startTime) {
            notify.error("Missing data", "Please select date and time");
            return;
        }

        bookingRequestInFlight.current = true;
        setIsBooking(true);
        const toastId = notify.loading("Creating booking...");

        try {
            const res = await bookingStore.book({
                trainerId: Number(id),
                date: booking.date,
                durationMinutes: booking.durationMinutes,
                startTime: `${booking.startTime}:00`,
            });

            await Promise.all([
                paymentStore.init(),
                refreshWorktimes(),
            ]);

            const payment = res.payment;

            if (payment && payment.method === PaymentMethodEnum.CARD) {
                notify.dismiss(toastId);
                const clientSecret = await createStripeIntent(payment.id);
                setStripeClientSecret(clientSecret);
            } else {
                notify.success(
                    "Training booked",
                    `${res.durationMinutes} min on ${res.date} paid from inner balance.`,
                    toastId,
                );
            }
        } catch (error: unknown) {
            notify.error(
                "Booking failed",
                getErrorMessage(error),
                toastId,
            );
        } finally {
            bookingRequestInFlight.current = false;
            setIsBooking(false);
        }
    };

    const formattedPrice = new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format(initialTrainer.pricePerHour / 100);
    const trainerPhotoUrl = initialTrainer.photoPath
        ? resolveStorageUrl(initialTrainer.photoPath, "")
        : null;

    return (
        <section className="min-w-[300px] mt-30">
            <motion.div
                onViewportEnter={() => setSelectedPage(SelectedPage.OurTrainers)}
                className="flex flex-col gap-5 mx-auto min-h-full w-5/6 m-20"
            >
                <motion.div
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true, amount: 0.5 }}
                    transition={{ duration: 0.5 }}
                    variants={{
                        hidden: { opacity: 0, x: -50 },
                        visible: { opacity: 1, x: 0 },
                    }}
                >
                    <p className="text-4xl">{`${initialTrainer.firstName} ${initialTrainer.lastName}`}</p>
                </motion.div>
                <div className="flex flex-col sm:flex-row items-start gap-6 sm:gap-10">
                    <motion.div
                        className="border w-full sm:w-[90%] md:w-[400px] rounded-2xl relative aspect-[3/4] overflow-hidden bg-gray-100"
                        initial="hidden"
                        whileInView="visible"
                        viewport={{ once: true, amount: 0.5 }}
                        transition={{ delay: 0.2, duration: 0.5 }}
                        variants={{
                            hidden: { opacity: 0, y: 50 },
                            visible: { opacity: 1, y: 0 },
                        }}
                    >
                        {trainerPhotoUrl ? (
                            <Image
                                src={trainerPhotoUrl}
                                fill
                                alt={`Photo of ${initialTrainer.firstName} ${initialTrainer.lastName}`}
                                sizes="(max-width: 768px) 90vw, 400px"
                                className="rounded-2xl object-cover"
                                unoptimized
                            />
                        ) : (
                            <div className="flex h-full items-center justify-center text-gray-400">
                                No image available
                            </div>
                        )}
                    </motion.div>
                    <motion.div
                        className="flex flex-col flex-1 gap-5 w-full"
                        initial="hidden"
                        whileInView="visible"
                        viewport={{ once: true, amount: 0.5 }}
                        transition={{ delay: 0.2, duration: 0.5 }}
                        variants={{
                            hidden: { opacity: 0, x: 50 },
                            visible: { opacity: 1, x: 0 },
                        }}
                    >
                        <div className="bg-primary-100 p-2 rounded-2xl text-3xl flex flex-col flex-1 gap-10">
                            <p><span className="font-bold">Specialization: </span>{initialTrainer.trainingType.name}</p>
                            <p><span className="font-bold">Education: </span>{initialTrainer.education}</p>
                            <p><span className="font-bold">About: </span>{initialTrainer.about}</p>
                            <p><span className="font-bold">Price: </span>{formattedPrice}</p>
                        </div>
                        <ul className="flex flex-col gap-3 max-h-86 overflow-y-auto pr-2">
                            {worktimes.map((worktime) => (
                                <Worktime
                                    worktime={worktime}
                                    pricePerHour={initialTrainer.pricePerHour}
                                    key={worktime.id}
                                />
                            ))}
                        </ul>
                        <button
                            className={`rounded-md px-10 py-2 self-start ${isBooking
                                ? "cursor-not-allowed bg-gray-300 text-gray-500"
                                : "cursor-pointer bg-secondary-500 hover:bg-primary-500 hover:text-white"
                            }`}
                            onClick={handleBooking}
                            disabled={isBooking}
                        >
                            {isBooking ? "Booking..." : "Book Training"}
                        </button>
                    </motion.div>
                </div>
            </motion.div>

            {stripeClientSecret && (
                <StripeModal
                    clientSecret={stripeClientSecret}
                    onClose={() => setStripeClientSecret(null)}
                    onSuccess={() => {
                        setStripeClientSecret(null);
                        void Promise.all([
                            bookingStore.init(),
                            paymentStore.init(),
                            refreshWorktimes(),
                        ]);
                    }}
                    successTitle="Training Booked!"
                    successDescription="Your personal session has been successfully scheduled."
                />
            )}
        </section>
    );
};

export default TrainerPersonal;
