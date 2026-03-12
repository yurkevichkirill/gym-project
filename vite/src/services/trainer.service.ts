import axios from "axios";
import type TrainerData from "../types/trainer.type.ts";
import type {ApiResponse} from "../types/api-response.type.ts";

export default class TrainerService {
    static async getAll(page: string | null, limit: string | null, minPrice: string | null, maxPrice: string | null, trainingTypeId: string | null, sort: string | null): Promise<TrainerData[]>{

        const response = await axios.get<ApiResponse<TrainerData[]>>("http://localhost/api/trainers/", {
            params: {
                minPrice,
                maxPrice,
                trainingTypeId,
                sort,
                page,
                limit
            }
        });

        return response.data.data;
    }
}