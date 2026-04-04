import {makeAutoObservable, runInAction} from "mobx";
import BookingType from "@/types/booking/booking.type";
import MembershipType from "@/types/membership/membership.type";
import PaymentType from "@/types/payment.type";
import {bookTrainingApi, deleteBookingApi, getMyBookingsApi} from "@/api/bookings.api";
import {getMyPayments} from "@/api/payments.api";
import {authStore} from "@/store/AuthStore";
import {getMyMemberships} from "@/api/memberships.api";
import BookingCreateType from "@/types/booking/booking-create.type";

export interface ClientStore {
    bookings: BookingType[];
    memberships: MembershipType[];
    payments: PaymentType[];
    isLoading: boolean

    init: () => Promise<void>;
    bookTraining: (payload: BookingCreateType) => Promise<BookingType>
    deleteBooking: (id: number) => Promise<void>;
}

export const clientStore: ClientStore = {
    bookings: [],
    memberships: [],
    payments: [],
    isLoading: false,

    init: async () => {
        runInAction(() => {
            clientStore.isLoading = true;
        });

        try {
            const [bookings, memberships, payments] = await Promise.all([
                getMyBookingsApi(),
                getMyMemberships(),
                getMyPayments(),
            ]);

            runInAction(() => {
                clientStore.bookings = bookings;
                clientStore.memberships = memberships;
                clientStore.payments = payments;
            });
        } catch (e) {
            console.log(e);
        } finally {
            runInAction(() => {
                clientStore.isLoading = false;
            });
        }
    },

    bookTraining: async (payload: BookingCreateType): Promise<BookingType> => {
        const res = await bookTrainingApi(payload);

        await Promise.all([
            clientStore.init(),
            authStore.checkAuth(),
        ]);

        return res;
    },

    deleteBooking: async (id: number) => {
        await deleteBookingApi(id);

        runInAction(() => {
            clientStore.bookings = clientStore.bookings.filter((booking) => booking.id !== id);
        });

        await authStore.checkAuth();
    },
};

makeAutoObservable(clientStore);