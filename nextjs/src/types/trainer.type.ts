import type TrainingTypeData from "./training-type.type.ts";

export default interface TrainerData {
    id: number,
    firstName: string,
    lastName: string,
    trainingType: TrainingTypeData,
    pricePerHour: number,
    education: string,
    about: string,
    photoUrl: string,
}