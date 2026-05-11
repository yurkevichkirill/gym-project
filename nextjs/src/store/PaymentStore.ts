import {makeAutoObservable, runInAction} from "mobx";
import PaymentType from "@/types/payment/payment.type";
import {getMyPayments} from "@/api/client/payments.api";

export interface PaymentStore {
    payments: PaymentType[];
    isLoading: boolean;

    init: () => Promise<void>;
}

export const paymentStore: PaymentStore = {
    payments: [],
    isLoading: false,

    init: async () => {
        runInAction(() => {
            paymentStore.isLoading = true;
        });

        try {
            const payments = await getMyPayments();

            runInAction(() => {
                paymentStore.payments = payments;
            });
        } catch (e) {
            console.log(e);
        } finally {
            runInAction(() => {
                paymentStore.isLoading = false;
            });
        }
    },
};

makeAutoObservable(paymentStore);