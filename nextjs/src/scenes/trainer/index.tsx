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
import {apiPost} from "@/lib/apiClient";
import {book, BookingData} from "@/lib/apiBooking";

const TrainerPersonal = ({ id }: { id: string }) => {
    const { setSelectedPage } = useNavigation();

    const [trainer, setTrainer] = useState<TrainerData>();
    const [worktimes, setWorktimes] = useState<WorktimeData[]>([]);
    const [date, setDate] = useState<string | null>(null);
    const [startTime, setStartTime] = useState<string | null>(null);
    const [endTime, setEndTime] = useState<string | null>(null);
    const [curWorktime, setCurWorkTime] = useState<WorktimeData | null>(null);

    const onClick = async ({ durationMinutes, startTime, workTimeId }: BookingData) => {
        try {
            await book({ durationMinutes, startTime, workTimeId });
        } catch (e) {
            console.error(e);
        }
    };

    useEffect(() => {
        const fetchTrainer = async () => {
            const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/trainers/${id}/`);
            if (!response.ok) {
                console.log("Failed to fetch trainers, status:  ", response.status);
            }
            const obj: ApiResponse<TrainerData> = await response.json();

            setTrainer(obj.data);
        };

        const fetchWorktime = async () => {
            const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/trainers/${id}/worktime/`);
            if (!response.ok) {
                console.log("Failed to fetch trainers, status:  ", response.status);
            }
            const obj: ApiResponse<WorktimeData[]> = await response.json();

            setWorktimes(obj.data);
        }

        void fetchTrainer();
        void fetchWorktime();
    }, [id]);

    // const diffInMs
    // const durationMinutes =

    return (
        <section className="min-w-[300px]">
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
                                { trainer?.pricePerHour }
                            </p>
                        </div>
                        <ul className="flex flex-col gap-3 max-h-86 overflow-y-auto pr-2">
                            { worktimes.map((worktime: WorktimeData) => (
                                <Worktime
                                    curWorktime = { curWorktime }
                                    setCurWorktime = { setCurWorkTime }
                                    worktime = { worktime }
                                    date = { date }
                                    setDate = { setDate }
                                    startTime = { startTime }
                                    setStartTime = { setStartTime }
                                    endTime = { endTime }
                                    setEndTime = { setEndTime }
                                    key = { worktime.id }
                                />
                            ))}
                        </ul>
                        {/*<button*/}
                        {/*    className="rounded-md bg-secondary-500 px-10 py-2 hover:bg-primary-500 hover:text-white self-start"*/}
                        {/*    onClick={async () => {*/}
                        {/*        durationMinutes && startTime && curWorktime &&*/}
                        {/*        onClick({ durationMinutes, startTime, curWorktime.id })*/}
                        {/*    }}*/}
                        {/*>*/}
                        {/*    Book Training*/}
                        {/*</button>*/}
                    </motion.div>
                </div>
            </motion.div>
        </section>
    );
}

export default TrainerPersonal;