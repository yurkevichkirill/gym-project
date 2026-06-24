import {makeAutoObservable, runInAction} from "mobx";
import PaymentType from "@/types/payment/payment.type";
import {getMyPayments} from "@/api/client/payments.api";
import {getErrorMessage} from "@/lib/getErrorMessage";
import {authStore} from "@/store/AuthStore";

type InitTask = {
    generation: number;
    promise: Promise<void>;
};

class PaymentStore {
    public payments: PaymentType[] = [];
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
        if (!authStore.isAuth) {
            this.reset();
            return Promise.resolve();
        }

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

    public reset(): void {
        this.generation += 1;
        this.initTask = null;
        this.payments = [];
        this.isLoading = false;
        this.error = null;
    }

    private async load(generation: number): Promise<void> {
        runInAction(() => {
            this.isLoading = true;
            this.error = null;
        });

        try {
            const payments = await getMyPayments();

            if (generation === this.generation && authStore.isAuth) {
                runInAction(() => {
                    this.payments = payments;
                });
            }
        } catch (error: unknown) {
            if (generation === this.generation && authStore.isAuth) {
                runInAction(() => {
                    this.error = getErrorMessage(error, "Failed to load payments.");
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

export const paymentStore = new PaymentStore();
