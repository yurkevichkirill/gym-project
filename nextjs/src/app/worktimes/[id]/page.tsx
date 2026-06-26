import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { getWorktime } from "@/api/public/worktime.api";
import { ApiClientError } from "@/lib/apiClient";
import WorktimeDetails from "@/scenes/worktime/WorktimeDetails";
import type WorktimeData from "@/types/trainer/public/worktime.type";

type Props = {
    params: Promise<{ id: string }>;
};

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
    title: "Worktime Details",
};

const WorktimePage = async ({ params }: Props) => {
    const { id } = await params;
    const parsedId = Number(id);

    if (!/^\d+$/.test(id) || !Number.isSafeInteger(parsedId) || parsedId <= 0) {
        notFound();
    }

    let worktime: WorktimeData;

    try {
        worktime = await getWorktime(id);
    } catch (error: unknown) {
        if (error instanceof ApiClientError && error.status === 404) {
            notFound();
        }

        throw error;
    }

    return (
        <main className="px-6 pb-20 pt-32">
            <WorktimeDetails worktime={worktime} />
        </main>
    );
};

export default WorktimePage;
