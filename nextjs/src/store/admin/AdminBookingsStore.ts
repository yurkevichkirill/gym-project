import { action, makeObservable, observable, runInAction } from "mobx";
import {
    cancelAdminBooking,
    createAdminClientBooking,
    getAdminBooking,
    getAdminBookings,
    getAdminBookingsRequestKey,
} from "@/api/admin/bookings.api";
import { AdminResourceStore } from "@/store/admin/createAdminResourceStore";
import type {
    AdminBooking,
    AdminBookingCreateRequest,
    AdminBookingsGetQueryParams,
} from "@/types/admin/admin-booking.type";
import { getAdminErrorMessage } from "@/api/admin/admin-api-utils";

class AdminBookingsStore extends AdminResourceStore<AdminBooking, AdminBookingsGetQueryParams> {
    public isCreating = false;

    public constructor() {
        super(getAdminBookings, getAdminBookingsRequestKey, getAdminBooking);
        makeObservable(this, {
            isCreating: observable,
            createForClient: action.bound,
            cancel: action.bound,
        });
    }

    public async createForClient(clientId: number, payload: AdminBookingCreateRequest): Promise<AdminBooking> {
        runInAction(() => {
            this.isCreating = true;
            this.mutationError = null;
        });

        try {
            const booking = await createAdminClientBooking(clientId, payload);
            await this.refetch();
            return booking;
        } catch (error: unknown) {
            runInAction(() => {
                this.mutationError = getAdminErrorMessage(error, "Failed to create booking.");
            });
            throw error;
        } finally {
            runInAction(() => {
                this.isCreating = false;
            });
        }
    }

    public cancel(id: number): Promise<AdminBooking | void> {
        return this.runAction(id, "cancel", () => cancelAdminBooking(id), "Failed to cancel booking.");
    }
}

export const adminBookingsStore = new AdminBookingsStore();

