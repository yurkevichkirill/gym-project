import type FreeSlotData from "./free-slot.type";

export default interface WorktimeData {
    id: number;
    trainerId: number;
    trainingTypeId: number;
    date: string;
    freeSlots: FreeSlotData[];
}
