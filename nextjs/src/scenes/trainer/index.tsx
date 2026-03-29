'use client'

import {SelectedPage} from "@/shared/types";
import type TrainerData from "@/types/trainer.type";
import {useEffect, useState} from "react";
import {motion} from "framer-motion";
import type WorktimeData from "@/types/worktime.type";
import Worktime from "@/scenes/worktime/Worktime";
import {useNavigation} from "@/context/navigation-context";
import {ApiResponse} from "@/types/api-response.type";
import Image from "next/image";
import {bookTraining} from "@/api/bookings.api";
import {getTrainer, getTrainers} from "@/api/public/trainers.api";
import {getWorktimes} from "@/api/public/worktime.api";

const TrainerPersonal = ({ id }: { id: string }) => {
    const { setSelectedPage } = useNavigation();

    const [trainer, setTrainer] = useState<TrainerData>();
    const [worktimes, setWorktimes] = useState<WorktimeData[]>([]);
    const [date, setDate] = useState<string | null>(null);
    const [startTime, setStartTime] = useState<string | null>(null);
    const [durationMinutes, setDurationMinutes] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchTrainer = async () => {
            try {
                const data = await getTrainer(id);
                setTrainer(data);
            } catch (e) {
                console.error(e);

                if (e instanceof Error) {
                    setError(e.message);
                } else {
                    setError("Something went wrong");
                }
            } finally {
                setLoading(false);
            }
        };
        const fetchWorktime = async () => {
            try {
                const data = await getWorktimes(id);
                setWorktimes(data);
            } catch (e) {
                console.error(e);

                if (e instanceof Error) {
                    setError(e.message);
                } else {
                    setError("Something went wrong");
                }
            } finally {
                setLoading(false);
            }
        }

        void fetchTrainer();
        void fetchWorktime();
    }, [id]);

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
                            </p>
                            <p>
                                <span className="font-bold">Experience: </span>
                            </p>
                            <p>
                                <span className="font-bold">Price: </span>
                                { trainer?.pricePerHour }$
                            </p>
                        </div>
                        <ul className="flex flex-col gap-3 max-h-86 overflow-y-auto pr-2">
                            { worktimes.map((worktime: WorktimeData) => (
                                <Worktime
                                    worktime = { worktime }
                                    date = { date }
                                    setDate = { setDate }
                                    startTime = { startTime }
                                    setStartTime = { setStartTime }
                                    duration = { durationMinutes }
                                    setDuration = { setDurationMinutes }
                                    pricePerHour = {trainer.pricePerHour}
                                    key = { worktime.id }
                                />
                            ))}
                        </ul>
                        <button
                            className="rounded-md bg-secondary-500 px-10 py-2 hover:bg-primary-500 hover:text-white self-start"
                            onClick={() => (
                                date && durationMinutes && startTime &&
                                bookTraining({ trainerId: Number(id), date, durationMinutes, startTime: startTime + ":00" })
                            )}
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