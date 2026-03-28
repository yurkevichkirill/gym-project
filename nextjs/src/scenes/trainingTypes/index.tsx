'use client'

import {useEffect, useState} from "react";
import {ApiResponse} from "@/types/api-response.type";
import TrainingTypeData from "@/types/training-type.type";
import {useNavigation} from "@/context/navigation-context";
import { motion } from "framer-motion";
import {SelectedPage} from "@/shared/types";
import TrainingType from "@/scenes/trainingTypes/trainingType";
import {getTrainers} from "@/api/public/trainers.api";
import {getTrainingTypes} from "@/api/public/training-types.api";

const trainingTypes = () => {const { setSelectedPage } = useNavigation();
    const [trainingTypes, setTrainingTypes] = useState<TrainingTypeData[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const data = await getTrainingTypes();
                setTrainingTypes(data);
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
        return <div>Error: {error}</div>;
    }

    if (error) {
        return <div>Error: {error}</div>;
    }

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