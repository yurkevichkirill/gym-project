import type TrainingTypeData from "../../training-type.type";

export default interface TrainerData {
    id: number,
    firstName: string,
    lastName: string,
    trainingType: TrainingTypeData,
    pricePerHour: number,
    education: string | null,
    about: string | null,
    photoPath: string | null,
}
