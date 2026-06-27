import { makeAutoObservable, runInAction } from "mobx";
import BookingType from "@/types/booking/booking.type";
import {
    cancelBooking,
    createBooking,
    getBookingsRequestKey,
    getMyBooking,
    getMyBookings,
} from "@/api/client/bookings.api";
import BookingCreateType from "@/types/booking/booking-create.type";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { clientStore } from "@/store/ClientStore";
import { membershipStore } from "@/store/MembershipStore";
import { paymentStore } from "@/store/PaymentStore";
import { authStore } from "@/store/AuthStore";
import { ApiClientError } from "@/lib/apiClient";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { BookingsGetQueryParams } from "@/types/booking/bookings-get.type";

type BookingPagination = ApiCollectionResponse<BookingType[]>["meta"]["pagination"];

type InitTask = {
    generation: number;
    requestId: number;
    requestKey: string;
    promise: Promise<void>;
};

type DetailTask = {
    generation: number;
    requestId: number;
    bookingId: number;
    promise: Promise<void>;
};

type BookingStorePrivateKey =
    | "generation"
    | "listRequestId"
    | "detailRequestId"
    | "currentParams"
    | "currentRequestKey"
    | "initTask"
    | "detailTask"
    | "cancelTasks";

const getErrorStatus = (error: unknown): number | null => {
    return error instanceof ApiClientError ? error.status : null;
};

class BookingStore {
    public bookings: BookingType[] = [];
    public pagination: BookingPagination | null = null;
    public sort: Record<string, string> = {};
    public loadedRequestKey: string | null = null;
    public isLoading = false;
    public isRefreshing = false;
    public error: string | null = null;
    public errorStatus: number | null = null;

    public selectedBooking: BookingType | null = null;
    public isDetailLoading = false;
    public detailError: string | null = null;
    public detailErrorStatus: number | null = null;

    public isCreating = false;
    public cancelingBookingIds: number[] = [];
    public availabilityRevision = 0;

    private generation = 0;
    private listRequestId = 0;
    private detailRequestId = 0;
    private currentParams: BookingsGetQueryParams = {};
    private currentRequestKey = "";
    private initTask: InitTask | null = null;
    private detailTask: DetailTask | null = null;
    private cancelTasks = new Map<number, Promise<BookingType>>();

    public constructor() {
        makeAutoObservable<this, BookingStorePrivateKey>(this, {
            generation: false,
            listRequestId: false,
            detailRequestId: false,
            currentParams: false,
            currentRequestKey: false,
            initTask: false,
            detailTask: false,
            cancelTasks: false,
        }, { autoBind: true });
    }

    public init(params: BookingsGetQueryParams = {}): Promise<void> {
        if (!authStore.isAuth) {
            this.reset();
            return Promise.resolve();
        }

        const generation = this.generation;
        const requestKey = getBookingsRequestKey(params);

        this.currentParams = { ...params };
        this.currentRequestKey = requestKey;

        if (
            this.initTask?.generation === generation
            && this.initTask.requestKey === requestKey
        ) {
            return this.initTask.promise;
        }

        const requestId = ++this.listRequestId;
        const promise = this.load(generation, requestId, params, requestKey).finally(() => {
            if (this.initTask?.requestId === requestId) {
                this.initTask = null;
            }
        });

        this.initTask = { generation, requestId, requestKey, promise };

        return promise;
    }

    public async loadBooking(bookingId: number): Promise<void> {
        if (!authStore.isAuth) {
            this.detailRequestId += 1;
            this.detailTask = null;
            this.resetDetail();
            return;
        }

        const generation = this.generation;

        if (
            this.detailTask?.generation === generation
            && this.detailTask.bookingId === bookingId
        ) {
            return this.detailTask.promise;
        }

        const requestId = ++this.detailRequestId;
        const promise = this.loadDetail(generation, requestId, bookingId).finally(() => {
            if (this.detailTask?.requestId === requestId) {
                this.detailTask = null;
            }
        });

        this.detailTask = { generation, requestId, bookingId, promise };

        return promise;
    }

    public isCanceling(bookingId: number): boolean {
        return this.cancelingBookingIds.includes(bookingId);
    }

    public async book(payload: BookingCreateType): Promise<BookingType> {
        if (this.isCreating) {
            throw new Error("A booking request is already in progress.");
        }

        const generation = this.generation;

        runInAction(() => {
            this.isCreating = true;
        });

        try {
            const booking = await createBooking(payload);

            if (generation === this.generation && authStore.isAuth) {
                runInAction(() => {
                    this.availabilityRevision += 1;
                });

                await this.syncAfterMutation();
            }

            return booking;
        } finally {
            if (generation === this.generation) {
                runInAction(() => {
                    this.isCreating = false;
                });
            }
        }
    }

    public cancel(id: number): Promise<BookingType> {
        const existingTask = this.cancelTasks.get(id);

        if (existingTask) {
            return existingTask;
        }

        runInAction(() => {
            this.cancelingBookingIds = [...this.cancelingBookingIds, id];
        });

        const task = this.cancelInternal(id).finally(() => {
            this.cancelTasks.delete(id);

            runInAction(() => {
                this.cancelingBookingIds = this.cancelingBookingIds.filter(
                    (bookingId) => bookingId !== id,
                );
            });
        });

        this.cancelTasks.set(id, task);

        return task;
    }

    public async refreshAfterPayment(bookingId: number): Promise<void> {
        if (!authStore.isAuth) {
            return;
        }

        await this.syncAfterMutation(bookingId);
    }

    public reset(): void {
        this.generation += 1;
        this.listRequestId += 1;
        this.detailRequestId += 1;
        this.initTask = null;
        this.detailTask = null;
        this.cancelTasks.clear();
        this.currentParams = {};
        this.currentRequestKey = "";
        this.bookings = [];
        this.pagination = null;
        this.sort = {};
        this.loadedRequestKey = null;
        this.isLoading = false;
        this.isRefreshing = false;
        this.error = null;
        this.errorStatus = null;
        this.isCreating = false;
        this.cancelingBookingIds = [];
        this.availabilityRevision = 0;
        this.resetDetail();
    }

    private async cancelInternal(id: number): Promise<BookingType> {
        const generation = this.generation;

        try {
            const booking = await cancelBooking(id);

            if (generation === this.generation && authStore.isAuth) {
                runInAction(() => {
                    this.availabilityRevision += 1;
                });

                await this.syncAfterMutation(id);
            }

            return booking;
        } catch (error: unknown) {
            if (
                generation === this.generation
                && authStore.isAuth
                && error instanceof ApiClientError
                && (error.status === 409 || error.status === 422)
            ) {
                await this.syncAfterMutation(id);
            }

            throw error;
        }
    }

    private async syncAfterMutation(bookingId?: number): Promise<void> {
        const detailId = bookingId !== undefined && this.selectedBooking?.id === bookingId
            ? bookingId
            : null;
        const tasks: Promise<void>[] = [
            this.refreshList(),
            clientStore.init(),
            membershipStore.init(),
            paymentStore.init(),
        ];

        if (detailId !== null) {
            tasks.push(this.refreshDetail(detailId));
        }

        await Promise.all(tasks);
    }

    private refreshList(): Promise<void> {
        this.initTask = null;

        return this.init(this.currentParams);
    }

    private refreshDetail(bookingId: number): Promise<void> {
        this.detailTask = null;

        return this.loadBooking(bookingId);
    }

    private resetDetail(): void {
        this.selectedBooking = null;
        this.isDetailLoading = false;
        this.detailError = null;
        this.detailErrorStatus = null;
    }

    private async load(
        generation: number,
        requestId: number,
        params: BookingsGetQueryParams,
        requestKey: string,
    ): Promise<void> {
        const hasExistingResponse = this.loadedRequestKey !== null;

        runInAction(() => {
            this.isLoading = !hasExistingResponse;
            this.isRefreshing = hasExistingResponse;
            this.error = null;
            this.errorStatus = null;
        });

        try {
            const response = await getMyBookings(params);

            if (
                generation === this.generation
                && requestId === this.listRequestId
                && requestKey === this.currentRequestKey
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.bookings = response.data;
                    this.pagination = response.meta.pagination;
                    this.sort = response.meta.sort ?? {};
                    this.loadedRequestKey = requestKey;
                });
            }
        } catch (error: unknown) {
            if (
                generation === this.generation
                && requestId === this.listRequestId
                && requestKey === this.currentRequestKey
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.error = getErrorMessage(error, "Failed to load bookings.");
                    this.errorStatus = getErrorStatus(error);
                });
            }
        } finally {
            if (
                generation === this.generation
                && requestId === this.listRequestId
                && requestKey === this.currentRequestKey
            ) {
                runInAction(() => {
                    this.isLoading = false;
                    this.isRefreshing = false;
                });
            }
        }
    }

    private async loadDetail(
        generation: number,
        requestId: number,
        bookingId: number,
    ): Promise<void> {
        runInAction(() => {
            if (this.selectedBooking?.id !== bookingId) {
                this.selectedBooking = null;
            }

            this.isDetailLoading = true;
            this.detailError = null;
            this.detailErrorStatus = null;
        });

        try {
            const booking = await getMyBooking(bookingId);

            if (
                generation === this.generation
                && requestId === this.detailRequestId
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.selectedBooking = booking;
                });
            }
        } catch (error: unknown) {
            if (
                generation === this.generation
                && requestId === this.detailRequestId
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.detailError = getErrorMessage(error, "Failed to load booking details.");
                    this.detailErrorStatus = getErrorStatus(error);
                });
            }
        } finally {
            if (generation === this.generation && requestId === this.detailRequestId) {
                runInAction(() => {
                    this.isDetailLoading = false;
                });
            }
        }
    }
}

export const bookingStore = new BookingStore();
