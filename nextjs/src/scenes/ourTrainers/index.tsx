'use client'

import {SelectedPage} from "@/shared/types";
import {useEffect, useState} from "react";
import type TrainerData from "@/types/trainer.type";
import { motion } from "framer-motion";
import Trainers from "@/scenes/ourTrainers/Trainers";
import {useNavigation} from "@/context/navigation-context";
import HText from "@/shared/HText";
import {getTrainers} from "@/api/public/trainers.api";

const OurTrainers = () => {
    const { setSelectedPage } = useNavigation();
    const [ourTrainers, setOurTrainers] = useState<TrainerData[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const data = await getTrainers();
                setOurTrainers(data);
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

        void fetchData();
    }, []);

    if (loading) {
        return <div>Loading ...</div>;
    }

    if (error) {
        return <div>Error: {error}</div>;
    }

    return (
        <section id="ourtrainers" className="mx-auto min-h-full w-5/6 py-20">
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
                <div className="md:w-3/5">
                    <HText>OUR TRAINERS</HText>
                    <p className="py-5">
                        Shatter your limits with our feral trainer legion: Powerlifting Overlords,
                        HIIT Battle Commanders, Ruthless Bodybuilding Beasts. Elite coaches who've
                        crushed world records and forged ironclad physiques in the fires of war.
                        No weaklings. Only scarred veterans who drag you past total failure. Train
                        under gods. Annihilate frailty.
                    </p>
                </div>
            </motion.div>
            <motion.div onViewportEnter={() => setSelectedPage(SelectedPage.OurTrainers)}>
                {ourTrainers.length > 0 && <Trainers trainers={ourTrainers} />}
            </motion.div>
        </section>
    );
}

export default OurTrainers;