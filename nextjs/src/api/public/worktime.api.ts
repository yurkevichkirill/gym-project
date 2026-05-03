import WorktimeData from "@/types/trainer/public/worktime.type";
import {ApiCollectionResponse} from "@/types/api-collection-response";

export const getWorktimes = async (trainerId: string): Promise<WorktimeData[]> => {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/trainers/${trainerId}/worktime/`);
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }
    const obj: ApiCollectionResponse<WorktimeData[]> = await response.json();

    return obj.data;
}