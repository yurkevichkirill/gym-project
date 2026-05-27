import {useEffect, useState} from "react";
import type TrainerData from "@/types/trainer/public/trainer.type";
import type WorktimeData from "@/types/trainer/public/worktime.type";
import {getTrainer} from "@/api/public/trainers.api";
import {getWorktimes} from "@/api/public/worktime.api";

export const useTrainerData = (id: string) => {
    const [trainer, setTrainer] = useState<TrainerData>();
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
            } catch (e) {
                console.error(e);
                setError(e instanceof Error ? e.message : "Something went wrong");
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
    }
}