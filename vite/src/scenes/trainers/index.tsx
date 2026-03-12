import type TrainerData from "../../types/trainer.type.ts";
import Trainer from "./Trainer.tsx";

type Props = {
    trainers: Array<TrainerData>
}

const Trainers = ({ trainers }: Props) => {
    return (
        <div>
            {trainers.map(trainer => (
                <Trainer
                    key = { trainer.id }
                    id = { trainer.id }
                    name = { trainer.firstName + " " + trainer.lastName }
                    trainingTypeName = { trainer.trainingType.name }
                    pricePerHour = { trainer.pricePerHour }
                >
                </Trainer>
            ))}
        </div>
    )
}

export default Trainers;