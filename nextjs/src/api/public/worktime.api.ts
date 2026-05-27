import WorktimeData from "@/types/trainer/public/worktime.type";
import {ApiCollectionResponse} from "@/types/api-collection-response";
import { GetWorktimesType } from "@/types/worktime/worktimes-get.type";

export const getWorktimes = async (params: GetWorktimesType = {}): Promise<WorktimeData[]> => {
    const searchParams = new URLSearchParams();
    
    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
            searchParams.append(key, value.toString());
        }
    });

    const queryString = searchParams.toString();
    const url = `${process.env.NEXT_PUBLIC_API_URL}/worktime/${queryString ? `?${queryString}` : ''}`;

    const response = await fetch(url);
    
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }
    
    const obj: ApiCollectionResponse<WorktimeData[]> = await response.json();

    return obj.data;
}