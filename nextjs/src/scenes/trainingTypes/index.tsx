'use client'

import Link from "next/link";
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
        <section id="trainingtypes" className="mx-4 mb-10 mt-10">
            <motion.div onViewportEnter={() => setSelectedPage(SelectedPage.TrainingTypes)}>
                {error ? (
                    <p role="alert" className="rounded-xl bg-red-50 p-4 text-red-700">
                        {error}
                    </p>
                ) : (
                    <>
                        <div className="columns-2 gap-4 space-y-2 md:columns-4">
                            {trainingTypes.map((trainingType) => (
                                <div key={trainingType.id} className="break-inside-avoid">
                                    <TrainingType {...trainingType} />
                                </div>
                            ))}
                        </div>
                        <div className="mt-8 flex justify-center">
                            <Link
                                href="/training-types"
                                className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white"
                            >
                                Browse all training types
                            </Link>
                        </div>
                    </>
                )}
            </motion.div>
        </section>
    );
};

export default TrainingTypes;
