import axios from "axios";
import type TrainerData from "../types/trainer.type.ts";
import type {ApiResponse} from "../types/api-response.type.ts";

export default class TrainerService {
    static async getAll(): Promise<TrainerData[]>{
        const response = await axios.get<ApiResponse<TrainerData[]>>("http://localhost/api/trainers");

        return response.data.data;
    }
}