'use client'

import {useEffect, useState} from "react";
import TrainingTypeData from "@/types/training-type.type";
import {useNavigation} from "@/context/navigation-context";
import { motion } from "framer-motion";
import {SelectedPage} from "@/shared/types";
import TrainingType from "@/scenes/trainingTypes/trainingType";
import {getTrainingTypes} from "@/api/public/training-types.api";
import {getErrorMessage} from "@/lib/getErrorMessage";

const TrainingTypes = () => {
    const { setSelectedPage } = useNavigation();
    const [trainingTypes, setTrainingTypes] = useState<TrainingTypeData[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const data = await getTrainingTypes();
                setTrainingTypes(data);
            } catch (error: unknown) {
                console.error(error);
                setError(getErrorMessage(error));
            } finally {
                setLoading(false);
            }
        };
        void fetchData();
    }, []);

    if (loading) {
        return <div>Loading ...</div>;
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
                            <TrainingType {...trainingType} />
                        </div>
                    ))}
                </div>
            </motion.div>
        </section>
    );
};

export default TrainingTypes;
