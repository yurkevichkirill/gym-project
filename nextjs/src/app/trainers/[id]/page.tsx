import { notFound } from "next/navigation";
import TrainerPersonal from "@/scenes/trainer";
import { BookingProvider } from "@/context/booking.context";
import { getTrainer } from "@/api/public/trainers.api";
import { getWorktimes } from "@/api/public/worktime.api";
import { ApiClientError } from "@/lib/apiClient";
import type TrainerData from "@/types/trainer/public/trainer.type";
import type WorktimeData from "@/types/trainer/public/worktime.type";

type Props = {
    params: Promise<{ id: string }>;
};

export const dynamic = "force-dynamic";

const TrainerPage = async ({ params }: Props) => {
    const { id } = await params;
    let trainer: TrainerData;
    let worktimes: WorktimeData[];

    try {
        [trainer, worktimes] = await Promise.all([
            getTrainer(id),
            getWorktimes({ trainerId: Number(id) }),
        ]);
    } catch (error: unknown) {
        if (error instanceof ApiClientError && error.status === 404) {
            notFound();
        }

        throw error;
    }

    return (
        <BookingProvider>
            <TrainerPersonal
                id={id}
                initialTrainer={trainer}
                initialWorktimes={worktimes}
            />
        </BookingProvider>
    );
};

export default TrainerPage;
