'use client'

import { SelectedPage } from "@/shared/types";
import { motion } from "framer-motion";
import type WorktimeData from "@/types/trainer/public/worktime.type";
import Worktime from "@/scenes/worktime/Worktime";
import { useNavigation } from "@/context/navigation-context";
import Image from "next/image";
import { notify } from "@/lib/notify";
import { useStore } from "@/store/StoreProvider";
import { useBooking } from "@/context/booking.context";
import { useTrainerData } from "@/hooks/useTrainerData";
import { useRef, useState } from "react";
import { PaymentMethodEnum } from "@/types/payment/payment-method.enum";
import { StripeModal } from "../stripe/stripeModal";
import { createStripeIntent } from "@/api/client/payments.api";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { resolveStorageUrl } from "@/lib/resolveStorageUrl";

const TrainerPersonal = ({ id }: { id: string }) => {
    const { setSelectedPage } = useNavigation();
    const { booking } = useBooking();
    const { bookingStore } = useStore();
    const { trainer, worktimes, loading, error } = useTrainerData(id);
    const [stripeClientSecret, setStripeClientSecret] = useState<string | null>(null);
    const [isBooking, setIsBooking] = useState(false);
    const bookingRequestInFlight = useRef(false);

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
                startTime: booking.startTime + ":00",
            });

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

    if (loading) return <div>Loading ...</div>;
    if (error) return <div>Error: {error}</div>;
    if (!trainer) return null;

    const formattedPrice = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(trainer.pricePerHour / 100);
    const trainerPhotoUrl = trainer.photoPath
        ? resolveStorageUrl(trainer.photoPath, "")
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
                        hidden: { opacity: 0, x:-50 },
                        visible: { opacity: 1, x: 0 },
                    }}
                >
                    <p className="text-4xl">{`${trainer.firstName} ${trainer.lastName}`}</p>
                </motion.div>
                <div className="flex flex-col sm:flex-row items-start gap-6 sm:gap-10">
                    <motion.div
                        className="border w-full sm:w-[90%] md:w-[400px] rounded-2xl relative aspect-[3/4] overflow-hidden bg-gray-100"
                        initial="hidden"
                        whileInView="visible"
                        viewport={{ once: true, amount: 0.5 }}
                        transition={{ delay:0.2, duration: 0.5 }}
                        variants={{
                            hidden: { opacity: 0, y: 50 },
                            visible: { opacity: 1, y: 0 },
                        }}
                    >
                        {trainerPhotoUrl ? (
                            <Image
                                src={trainerPhotoUrl}
                                fill
                                alt={`Photo of ${trainer.firstName} ${trainer.lastName}`}
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
                        transition={{ delay:0.2, duration: 0.5 }}
                        variants={{
                            hidden: { opacity: 0, x: 50 },
                            visible: { opacity: 1, x: 0 },
                        }}
                    >
                        <div className="bg-primary-100 p-2 rounded-2xl text-3xl flex flex-col flex-1 gap-10">
                            <p><span className="font-bold">Specialization: </span>{trainer.trainingType.name}</p>
                            <p><span className="font-bold">Education: </span>{trainer.education}</p>
                            <p><span className="font-bold">About: </span>{trainer.about}</p>
                            <p><span className="font-bold">Price: </span>{formattedPrice}</p>
                        </div>
                        <ul className="flex flex-col gap-3 max-h-86 overflow-y-auto pr-2">
                            {worktimes.map((worktime: WorktimeData) => (
                                <Worktime
                                    worktime={worktime}
                                    pricePerHour={trainer.pricePerHour}
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
                    onSuccess={() => setStripeClientSecret(null)}
                    successTitle="Training Booked!"
                    successDescription="Your personal session has been successfully scheduled."
                />
            )}
        </section>
    );
};

export default TrainerPersonal;
