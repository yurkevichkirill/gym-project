import { BookingStatusEnum } from "./bookings-status.enum";

export interface BookingsGetQueryParams {
    trainerId?: number;
    status?: BookingStatusEnum
    date?: string;
    startTime?: string;
    durationMinutes?: number;
    sort?: string;
    page?: number;
    limit?: number;
}