import {useEffect, useState} from "react";
import type {TrainerDetailsData} from "@/types/trainer/public/trainer.type";
import type WorktimeData from "@/types/trainer/public/worktime.type";
import {getTrainer} from "@/api/public/trainers.api";
import {getWorktimes} from "@/api/public/worktime.api";
import {getErrorMessage} from "@/lib/getErrorMessage";

export const useTrainerData = (id: string) => {
    const [trainer, setTrainer] = useState<TrainerDetailsData>();
    const [worktimes, setWorktimes] = useState<WorktimeData[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const [trainerData, worktimeData] = await Promise.all([
                    getTrainer(id),
                    getWorktimes({
                        trainerId: Number(id),
                    }),
                ]);

                setTrainer(trainerData);
                setWorktimes(worktimeData);
            } catch (error: unknown) {
                console.error(error);
                setError(getErrorMessage(error));
            } finally {
                setLoading(false);
            }
        };

        void fetchData();
    }, [id]);

    return {
        trainer,
        worktimes,
        loading,
        error,
    };
};
