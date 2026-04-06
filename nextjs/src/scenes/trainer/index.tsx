'use client'

import {SelectedPage} from "@/shared/types";
import {motion} from "framer-motion";
import type WorktimeData from "@/types/trainer/public/worktime.type";
import Worktime from "@/scenes/worktime/Worktime";
import {useNavigation} from "@/context/navigation-context";
import Image from "next/image";
import {notify} from "@/lib/notify";
import {useStore} from "@/store/StoreProvider";
import {useBooking} from "@/context/booking.context";
import {useTrainerData} from "@/hooks/useTrainerData";

const TrainerPersonal = ({ id }: { id: string }) => {
    const { setSelectedPage } = useNavigation();
    const { booking } = useBooking();
    const { clientStore } = useStore();
    const { trainer, worktimes, loading, error } = useTrainerData(id);

    const handleBooking = async () => {
        if (!id || !booking.date || !booking.durationMinutes || !booking.startTime) {
            notify.error("Missing data", "Please select date and time");
            return;
        }

        const toastId = notify.loading("Booking training...");

        try {
            const res = await clientStore.bookTraining({
                trainerId: Number(id),
                date: booking.date,
                durationMinutes: booking.durationMinutes,
                startTime: booking.startTime + ":00",
            });

            notify.success(
                "Training booked",
                `${res.durationMinutes} min on ${res.date} at ${res.startTime}`,
                toastId,
            );
        } catch (error: any) {
            notify.error(
                "Booking failed",
                error?.message || "Something went wrong",
                toastId,
            );
        }
    }

    if (loading) {
        return <div>Loading ...</div>;
    }

    if (error) {
        return <div>Error: {error}</div>;
    }

    if (!trainer) {
        return null;
    }

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
                    <p className="text-4xl">{ `${trainer?.firstName} ${trainer?.lastName}` }</p>
                </motion.div>
                <div className="flex flex-col sm:flex-row items-start gap-6 sm:gap-10">
                    <motion.div
                        className="border w-full sm:w-[90%] md:w-[400px] rounded-2xl relative aspect-[3/4]"
                        initial="hidden"
                        whileInView="visible"
                        viewport={{ once: true, amount: 0.5 }}
                        transition={{ delay:0.2, duration: 0.5 }}
                        variants={{
                            hidden: { opacity: 0, y: 50 },
                            visible: { opacity: 1, y: 0 },
                        }}
                    >
                        {trainer?.photoUrl &&
                            <Image src={ trainer?.photoUrl } fill alt="Icon" className="rounded-2xl object-cover"/>
                        }
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
                        <div
                            className="bg-primary-100 p-2 rounded-2xl text-3xl flex flex-col flex-1 gap-10">
                            <p>
                                <span className="font-bold">Specialization: </span>
                                {trainer?.trainingType.name }
                            </p>
                            <p>
                                <span className="font-bold">Education: </span>
                                {trainer.education}
                            </p>
                            <p>
                                <span className="font-bold">About: </span>
                                {trainer.about}
                            </p>
                            <p>
                                <span className="font-bold">Price: </span>
                                { trainer.pricePerHour }$
                            </p>
                        </div>
                        <ul className="flex flex-col gap-3 max-h-86 overflow-y-auto pr-2">
                            { worktimes.map((worktime: WorktimeData) => (
                                <Worktime
                                    worktime = { worktime }
                                    pricePerHour = {trainer.pricePerHour}
                                    key = { worktime.id }
                                />
                            ))}
                        </ul>
                        <button
                            className="rounded-md bg-secondary-500 px-10 py-2 hover:bg-primary-500 hover:text-white self-start"
                            onClick={handleBooking}
                        >
                            Book Training
                        </button>
                    </motion.div>
                </div>
            </motion.div>
        </section>
    );
}

export default TrainerPersonal;