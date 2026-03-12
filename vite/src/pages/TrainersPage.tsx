import {useEffect, useState} from "react";
import TrainerService from "../services/trainer.service.ts";
import type TrainerData from "../types/trainer.type.ts";
import Trainers from "../scenes/trainers";

export default function TrainersPage() {
    const [trainers, setTrainers] = useState<TrainerData[]>([]);
    useEffect(() => {
        const fetchData = async () => {
            const trainers = await TrainerService.getAll();
            setTrainers(trainers);
        }

        void fetchData();
    }, []);

    return <Trainers trainers={trainers} />
}