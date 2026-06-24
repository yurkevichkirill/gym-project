'use client'

import TrainingTypeData from "@/types/training-type.type";
import { useNavigation } from "@/context/navigation-context";
import { motion } from "framer-motion";
import { SelectedPage } from "@/shared/types";
import TrainingType from "@/scenes/trainingTypes/trainingType";

type Props = {
    trainingTypes: TrainingTypeData[];
    error?: string | null;
};

const TrainingTypes = ({ trainingTypes, error = null }: Props) => {
    const { setSelectedPage } = useNavigation();

    return (
        <section id="trainingtypes" className="mb-10 mt-10 mx-4">
            <motion.div onViewportEnter={() => setSelectedPage(SelectedPage.TrainingTypes)}>
                {error ? (
                    <p role="alert" className="rounded-xl bg-red-50 p-4 text-red-700">
                        {error}
                    </p>
                ) : (
                    <div className="columns-2 md:columns-4 gap-4 space-y-2">
                        {trainingTypes.map((trainingType) => (
                            <div key={trainingType.id} className="break-inside-avoid">
                                <TrainingType {...trainingType} />
                            </div>
                        ))}
                    </div>
                )}
            </motion.div>
        </section>
    );
};

export default TrainingTypes;
