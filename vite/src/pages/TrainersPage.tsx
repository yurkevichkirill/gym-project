import {useEffect, useState} from "react";
import TrainerService from "../services/trainer.service.ts";
import type TrainerData from "../types/trainer.type.ts";
import Trainers from "../scenes/trainers";
import {useSearchParams} from "react-router-dom";

export default function TrainersPage() {
    const [trainers, setTrainers] = useState<TrainerData[]>([]);

    const [searchParams] = useSearchParams();

    const page = searchParams.get("page");
    const limit = searchParams.get("limit");

    const minPrice = searchParams.get("minPrice");
    const maxPrice = searchParams.get("maxPrice");
    const trainingTypeId = searchParams.get("trainingTypeId");

    const sort = searchParams.get("sort");
    useEffect(() => {
        const fetchData = async () => {
            const trainers = await TrainerService.getAll(page, limit, minPrice, maxPrice, trainingTypeId, sort);
            console.log(trainers);
            setTrainers(trainers);
        }

        void fetchData();
    }, [page, limit, minPrice, maxPrice, trainingTypeId, sort]);

    return <Trainers trainers={trainers} />
}