import {makeAutoObservable, runInAction} from "mobx";
import PaymentType from "@/types/payment/payment.type";
import {getMyPayments} from "@/api/client/payments.api";
import {getErrorMessage} from "@/lib/getErrorMessage";

export interface PaymentStore {
    payments: PaymentType[];
    isLoading: boolean;
    error: string | null;

    init: () => Promise<void>;
}

export const paymentStore: PaymentStore = {
    payments: [],
    isLoading: false,
    error: null,

    init: async () => {
        runInAction(() => {
            paymentStore.isLoading = true;
            paymentStore.error = null;
        });

        try {
            const payments = await getMyPayments();

            runInAction(() => {
                paymentStore.payments = payments;
            });
        } catch (error: unknown) {
            runInAction(() => {
                paymentStore.error = getErrorMessage(error, "Failed to load payments.");
            });
        } finally {
            runInAction(() => {
                paymentStore.isLoading = false;
            });
        }
    },
};

makeAutoObservable(paymentStore);
