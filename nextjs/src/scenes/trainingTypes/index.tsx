'use client'

import {useEffect, useState} from "react";
import {ApiResponse} from "@/types/api-response.type";
import TrainingTypeData from "@/types/training-type.type";
import {useNavigation} from "@/context/navigation-context";
import { motion } from "framer-motion";
import {SelectedPage} from "@/shared/types";
import TrainingType from "@/scenes/trainingTypes/trainingType";
import HText from "@/shared/HText";

const trainingTypes = () => {const { setSelectedPage } = useNavigation();
    const [trainingTypes, setTrainingTypes] = useState<TrainingTypeData[]>([]);

    useEffect(() => {
        const fetchData = async () => {
            const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/training/types`);
            const trainingTypes: ApiResponse<TrainingTypeData[]> = await response.json();

            setTrainingTypes(trainingTypes.data);
        }

        fetchData();
    }, []);

    return (
        <section id="trainingtypes" className="mb-10 mt-10 mx-4">
            <motion.div onViewportEnter={() => setSelectedPage(SelectedPage.TrainingTypes)}>
                <div className="columns-2 md:columns-4 gap-4 space-y-2">
                    {trainingTypes.map((trainingType) => (
                        <div key={trainingType.id} className="break-inside-avoid">
                            <TrainingType {...trainingType} /></div>
                    ))}
                </div>
            </motion.div>
        </section>
    );
}

export default trainingTypes;