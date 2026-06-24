import type FreeSlotData from "./free-slot.type";

export default interface WorktimeData {
    id: number,
    date: string,
    freeSlots: FreeSlotData[],
}
