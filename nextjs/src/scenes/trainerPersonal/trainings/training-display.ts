import { BookingStatusEnum } from "@/types/booking/bookings-status.enum";

export const canUpdateTraining = (status: BookingStatusEnum): boolean => {
    return status === BookingStatusEnum.SCHEDULED;
};

export const canCancelTraining = (status: BookingStatusEnum): boolean => {
    return status === BookingStatusEnum.PENDING
        || status === BookingStatusEnum.SCHEDULED;
};

export const canCompleteTraining = (status: BookingStatusEnum): boolean => {
    return status === BookingStatusEnum.SCHEDULED;
};

export const getTrainingBusyLabel = (isBusy: boolean): string => {
    return isBusy ? "Busy" : "Released";
};
