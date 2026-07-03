import { makeAutoObservable, runInAction } from "mobx";
import PaymentType from "@/types/payment/payment.type";
import {
    createStripeIntent,
    getPaymentForScope,
    getPaymentsForScope,
    getPaymentsRequestKey,
    PaymentScope,
} from "@/api/client/payments.api";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { authStore } from "@/store/AuthStore";
import { ApiClientError } from "@/lib/apiClient";
import { ApiCollectionResponse } from "@/types/api-collection-response";
import { PaymentsGetQueryParams } from "@/types/payment/payments-get.type";
import { PaymentStatusEnum } from "@/types/payment/payment-status.enum";

type PaymentPagination = ApiCollectionResponse<PaymentType[]>["meta"]["pagination"];

type InitTask = {
    generation: number;
    requestId: number;
    requestKey: string;
    scope: PaymentScope;
    promise: Promise<void>;
};

type DetailTask = {
    generation: number;
    requestId: number;
    paymentId: number;
    scope: PaymentScope;
    promise: Promise<void>;
};

type PaymentStorePrivateKey =
    | "generation"
    | "listRequestId"
    | "detailRequestId"
    | "currentParams"
    | "currentScope"
    | "currentRequestKey"
    | "initTask"
    | "detailTask"
    | "intentTasks";

const getErrorStatus = (error: unknown): number | null => {
    return error instanceof ApiClientError ? error.status : null;
};

const delay = (milliseconds: number): Promise<void> => {
    return new Promise((resolve) => window.setTimeout(resolve, milliseconds));
};

class PaymentStore {
    public payments: PaymentType[] = [];
    public pagination: PaymentPagination | null = null;
    public sort: Record<string, string> = {};
    public loadedRequestKey: string | null = null;
    public isLoading = false;
    public isRefreshing = false;
    public error: string | null = null;
    public errorStatus: number | null = null;

    public selectedPayment: PaymentType | null = null;
    public selectedPaymentScope: PaymentScope | null = null;
    public isDetailLoading = false;
    public detailError: string | null = null;
    public detailErrorStatus: number | null = null;

    public creatingIntentPaymentIds: number[] = [];

    private generation = 0;
    private listRequestId = 0;
    private detailRequestId = 0;
    private currentParams: PaymentsGetQueryParams = {};
    private currentScope: PaymentScope = PaymentScope.CLIENT;
    private currentRequestKey = "";
    private initTask: InitTask | null = null;
    private detailTask: DetailTask | null = null;
    private intentTasks = new Map<number, Promise<string>>();

    public constructor() {
        makeAutoObservable<this, PaymentStorePrivateKey>(this, {
            generation: false,
            listRequestId: false,
            detailRequestId: false,
            currentParams: false,
            currentScope: false,
            currentRequestKey: false,
            initTask: false,
            detailTask: false,
            intentTasks: false,
        }, { autoBind: true });
    }

    public init(
        params: PaymentsGetQueryParams = {},
        scope: PaymentScope = PaymentScope.CLIENT,
    ): Promise<void> {
        if (!authStore.isAuth) {
            this.reset();
            return Promise.resolve();
        }

        const generation = this.generation;
        const requestKey = getPaymentsRequestKey(params, scope);

        this.currentParams = { ...params };
        this.currentScope = scope;
        this.currentRequestKey = requestKey;

        if (
            this.initTask?.generation === generation
            && this.initTask.requestKey === requestKey
            && this.initTask.scope === scope
        ) {
            return this.initTask.promise;
        }

        const requestId = ++this.listRequestId;
        const promise = this.load(
            generation,
            requestId,
            params,
            scope,
            requestKey,
        ).finally(() => {
            if (this.initTask?.requestId === requestId) {
                this.initTask = null;
            }
        });

        this.initTask = {
            generation,
            requestId,
            requestKey,
            scope,
            promise,
        };

        return promise;
    }

    public loadPayment(
        paymentId: number,
        scope: PaymentScope = PaymentScope.CLIENT,
    ): Promise<void> {
        if (!authStore.isAuth) {
            this.detailRequestId += 1;
            this.detailTask = null;
            this.resetDetail();
            return Promise.resolve();
        }

        const generation = this.generation;

        if (
            this.detailTask?.generation === generation
            && this.detailTask.paymentId === paymentId
            && this.detailTask.scope === scope
        ) {
            return this.detailTask.promise;
        }

        const requestId = ++this.detailRequestId;
        const promise = this.loadDetail(
            generation,
            requestId,
            paymentId,
            scope,
        ).finally(() => {
            if (this.detailTask?.requestId === requestId) {
                this.detailTask = null;
            }
        });

        this.detailTask = {
            generation,
            requestId,
            paymentId,
            scope,
            promise,
        };

        return promise;
    }

    public createIntent(paymentId: number): Promise<string> {
        const existingTask = this.intentTasks.get(paymentId);

        if (existingTask !== undefined) {
            return existingTask;
        }

        runInAction(() => {
            this.creatingIntentPaymentIds = [...this.creatingIntentPaymentIds, paymentId];
        });

        const task = createStripeIntent(paymentId).finally(() => {
            if (this.intentTasks.get(paymentId) === task) {
                this.intentTasks.delete(paymentId);

                runInAction(() => {
                    this.creatingIntentPaymentIds = this.creatingIntentPaymentIds.filter(
                        (id) => id !== paymentId,
                    );
                });
            }
        });

        this.intentTasks.set(paymentId, task);

        return task;
    }

    public isCreatingIntent(paymentId: number): boolean {
        return this.creatingIntentPaymentIds.includes(paymentId);
    }

    public async refreshAfterStripeReturn(paymentId: number): Promise<void> {
        for (let attempt = 0; attempt < 5; attempt += 1) {
            this.detailTask = null;
            await this.loadPayment(paymentId, PaymentScope.CLIENT);

            if (
                this.selectedPayment?.id !== paymentId
                || this.selectedPaymentScope !== PaymentScope.CLIENT
                || this.selectedPayment.status !== PaymentStatusEnum.PENDING
                || attempt === 4
            ) {
                break;
            }

            await delay(1200);
        }

        if (
            this.loadedRequestKey !== null
            && this.currentScope === PaymentScope.CLIENT
        ) {
            this.initTask = null;
            await this.init(this.currentParams, PaymentScope.CLIENT);
        }
    }

    public reset(): void {
        this.generation += 1;
        this.listRequestId += 1;
        this.detailRequestId += 1;
        this.initTask = null;
        this.detailTask = null;
        this.intentTasks.clear();
        this.currentParams = {};
        this.currentScope = PaymentScope.CLIENT;
        this.currentRequestKey = "";
        this.payments = [];
        this.pagination = null;
        this.sort = {};
        this.loadedRequestKey = null;
        this.isLoading = false;
        this.isRefreshing = false;
        this.error = null;
        this.errorStatus = null;
        this.creatingIntentPaymentIds = [];
        this.resetDetail();
    }

    private resetDetail(): void {
        this.selectedPayment = null;
        this.selectedPaymentScope = null;
        this.isDetailLoading = false;
        this.detailError = null;
        this.detailErrorStatus = null;
    }

    private async load(
        generation: number,
        requestId: number,
        params: PaymentsGetQueryParams,
        scope: PaymentScope,
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
            const response = await getPaymentsForScope(params, scope);

            if (
                generation === this.generation
                && requestId === this.listRequestId
                && requestKey === this.currentRequestKey
                && scope === this.currentScope
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.payments = response.data;
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
                && scope === this.currentScope
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.error = getErrorMessage(error, "Failed to load payments.");
                    this.errorStatus = getErrorStatus(error);
                });
            }
        } finally {
            if (
                generation === this.generation
                && requestId === this.listRequestId
                && requestKey === this.currentRequestKey
                && scope === this.currentScope
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
        paymentId: number,
        scope: PaymentScope,
    ): Promise<void> {
        runInAction(() => {
            if (
                this.selectedPayment?.id !== paymentId
                || this.selectedPaymentScope !== scope
            ) {
                this.selectedPayment = null;
                this.selectedPaymentScope = null;
            }

            this.isDetailLoading = true;
            this.detailError = null;
            this.detailErrorStatus = null;
        });

        try {
            const payment = await getPaymentForScope(paymentId, scope);

            if (
                generation === this.generation
                && requestId === this.detailRequestId
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.selectedPayment = payment;
                    this.selectedPaymentScope = scope;
                });
            }
        } catch (error: unknown) {
            if (
                generation === this.generation
                && requestId === this.detailRequestId
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.detailError = getErrorMessage(error, "Failed to load payment details.");
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

export const paymentStore = new PaymentStore();
