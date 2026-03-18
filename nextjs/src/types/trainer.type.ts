import type TrainingTypeData from "./training-type.type.ts";

export default interface TrainerData {
    id: string,
    firstName: string,
    lastName: string,
    trainingType: TrainingTypeData,
    pricePerHour: number,
    photoUrl: string,
}