import {ApiResponse} from "@/types/api-response.type";
import WorktimeData from "@/types/worktime.type";

export const getWorktimes = async (trainerId: string): Promise<WorktimeData[]> => {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/trainers/${trainerId}/worktime/?limit=20`);
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }
    const obj: ApiResponse<WorktimeData[]> = await response.json();

    return obj.data;
}