'use client'

import {SelectedPage} from "@/shared/types";
import {useEffect, useState} from "react";
import type TrainerData from "@/types/trainer.type";
import { motion } from "framer-motion";
import Trainers from "@/scenes/ourTrainers/Trainers";
import {useNavigation} from "@/context/navigation-context";
import {ApiResponse} from "@/types/api-response.type";

const OurTrainers = () => {
    const { setSelectedPage } = useNavigation();
    const [ourTrainers, setOurTrainers] = useState<TrainerData[]>([]);

    useEffect(() => {
        const fetchData = async () => {
            const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/trainers/`);
            if (!response.ok) {
                console.error("Failed to fetch trainers, status:  ", response.status);
            }
            const obj: ApiResponse<TrainerData[]> = await response.json();

            setOurTrainers(obj.data);
        }

        void fetchData();
    }, []);

    return (
        <section id="ourtrainers" className="mx-auto min-h-full w-5/6 py-20">
            <motion.div onViewportEnter={() => setSelectedPage(SelectedPage.OurTrainers)}>
                {ourTrainers.length > 0 && <Trainers trainers={ourTrainers} />}
            </motion.div>
        </section>
    );
}

export default OurTrainers;