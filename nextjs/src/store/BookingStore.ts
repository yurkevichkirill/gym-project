import {makeAutoObservable, runInAction} from "mobx";
import BookingType from "@/types/booking/booking.type";
import {createBooking, cancelBooking, getMyBookings} from "@/api/client/bookings.api";
import {authStore} from "@/store/AuthStore";
import BookingCreateType from "@/types/booking/booking-create.type";
import {getErrorMessage} from "@/lib/getErrorMessage";

export interface BookingStore {
    bookings: BookingType[];
    isLoading: boolean;
    error: string | null;

    init: () => Promise<void>;
    book: (payload: BookingCreateType) => Promise<BookingType>
    cancel: (id: number) => Promise<void>;
}

export const bookingStore: BookingStore = {
    bookings: [],
    isLoading: false,
    error: null,

    init: async () => {
        runInAction(() => {
            bookingStore.isLoading = true;
            bookingStore.error = null;
        });

        try {
            const bookings = await getMyBookings();

            runInAction(() => {
                bookingStore.bookings = bookings;
            });
        } catch (error: unknown) {
            runInAction(() => {
                bookingStore.error = getErrorMessage(error, "Failed to load bookings.");
            });
        } finally {
            runInAction(() => {
                bookingStore.isLoading = false;
            });
        }
    },

    book: async (payload: BookingCreateType): Promise<BookingType> => {
        const res = await createBooking(payload);

        await Promise.all([
            bookingStore.init(),
            authStore.checkAuth(),
        ]);

        return res;
    },

    cancel: async (id: number) => {
        await cancelBooking(id);

        runInAction(() => {
            bookingStore.bookings = bookingStore.bookings.filter((booking) => booking.id !== id);
        });

        await authStore.checkAuth();
    },
};

makeAutoObservable(bookingStore);
