'use client'

import { motion } from "framer-motion";
import type TrainerData from "@/types/trainer/public/trainer.type";
import OurTrainer from "@/scenes/ourTrainers/OurTrainer";

type Props = {
    trainers: Array<TrainerData>
}

const container = {
    hidden: {},
    visible: {
        transition: { staggerChildren: 0.1 }
    }
}

const Trainers = ({ trainers }: Props) => {
    return (
        <motion.div
            className="grid grid-cols-[repeat(auto-fit,minmax(250px,1fr))] gap-10"
            initial="hidden"
            animate="visible"
            whileInView="visible"
            viewport={{ once: true, amount: 0.1 }}
            variants={container}
        >
            {trainers.map(trainer => (
                <OurTrainer
                    key = { trainer.id }
                    id = { trainer.id }
                    name = { trainer.firstName + " " + trainer.lastName }
                    photoUrl={ trainer.photoUrl }
                >
                </OurTrainer>
            ))}
        </motion.div>
    )
}

export default Trainers;