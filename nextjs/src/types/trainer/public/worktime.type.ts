import type FreeSlotData from "./free-slot.type.ts";

export default interface WorktimeData {
    id: number,
    date: string,
    freeSlots: FreeSlotData[],
}