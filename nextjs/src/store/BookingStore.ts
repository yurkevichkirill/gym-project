import {makeAutoObservable, runInAction} from "mobx";
import BookingType from "@/types/booking/booking.type";
import {cancelBooking, createBooking, getMyBookings} from "@/api/client/bookings.api";
import BookingCreateType from "@/types/booking/booking-create.type";
import {getErrorMessage} from "@/lib/getErrorMessage";
import {clientStore} from "@/store/ClientStore";

type InitTask = {
    generation: number;
    promise: Promise<void>;
};

class BookingStore {
    public bookings: BookingType[] = [];
    public isLoading = false;
    public error: string | null = null;

    private generation = 0;
    private initTask: InitTask | null = null;

    public constructor() {
        makeAutoObservable(this, {
            generation: false,
            initTask: false,
        }, {autoBind: true});
    }

    public init(): Promise<void> {
        const generation = this.generation;
        if (this.initTask?.generation === generation) {
            return this.initTask.promise;
        }

        const promise = this.load(generation).finally(() => {
            if (this.initTask?.promise === promise) {
                this.initTask = null;
            }
        });

        this.initTask = {generation, promise};

        return promise;
    }

    public async book(payload: BookingCreateType): Promise<BookingType> {
        const generation = this.generation;
        const booking = await createBooking(payload);

        if (generation === this.generation) {
            await Promise.all([
                this.init(),
                clientStore.init(),
            ]);
        }

        return booking;
    }

    public async cancel(id: number): Promise<void> {
        const generation = this.generation;
        await cancelBooking(id);

        if (generation !== this.generation) {
            return;
        }

        runInAction(() => {
            this.bookings = this.bookings.filter((booking) => booking.id !== id);
        });

        await clientStore.init();
    }

    public reset(): void {
        this.generation += 1;
        this.initTask = null;
        this.bookings = [];
        this.isLoading = false;
        this.error = null;
    }

    private async load(generation: number): Promise<void> {
        runInAction(() => {
            this.isLoading = true;
            this.error = null;
        });

        try {
            const bookings = await getMyBookings();

            if (generation === this.generation) {
                runInAction(() => {
                    this.bookings = bookings;
                });
            }
        } catch (error: unknown) {
            if (generation === this.generation) {
                runInAction(() => {
                    this.error = getErrorMessage(error, "Failed to load bookings.");
                });
            }
        } finally {
            if (generation === this.generation) {
                runInAction(() => {
                    this.isLoading = false;
                });
            }
        }
    }
}

export const bookingStore = new BookingStore();
