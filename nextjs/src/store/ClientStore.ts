import { makeAutoObservable, runInAction } from "mobx";
import { authStore } from "@/store/AuthStore";
import ClientEditType from "@/types/client/client-edit.type";
import { apiPost, ApiClientError } from "@/lib/apiClient";
import { ApiItemResponse } from "@/types/api-item-response.type";
import ClientActivateType from "@/types/client/clientActivate.type";
import ClientType from "@/types/client/client.type";
import MembershipType from "@/types/membership/membership.type";
import {
    deleteCurrentClient,
    getCurrentClient,
    registerVisit,
    updateCurrentClient,
} from "@/api/client/profile.api";
import { getAllMemberships } from "@/api/client/memberships.api";
import { MembershipStatusEnum } from "@/types/membership/membership-status.enum";
import { getErrorMessage } from "@/lib/getErrorMessage";

type InitTask = {
    generation: number;
    promise: Promise<void>;
};

type VisitOverviewTask = {
    generation: number;
    requestId: number;
    promise: Promise<void>;
};

type ClientStorePrivateKey =
    | "generation"
    | "visitOverviewRequestId"
    | "initTask"
    | "visitOverviewTask"
    | "visitTask";

const getVisitErrorMessage = (
    error: unknown,
    hadActiveMembership: boolean,
): string => {
    if (error instanceof ApiClientError && (error.status === 400 || error.status === 403)) {
        return error.payload.message || (
            hadActiveMembership
                ? "The membership is no longer active or its visit limit has been reached."
                : "An active membership is required to register a visit."
        );
    }

    return getErrorMessage(error, "Failed to register the visit.");
};

class ClientStore {
    public client: ClientType | null = null;
    public isLoading = false;
    public error: string | null = null;

    public activeMembership: MembershipType | null = null;
    public isVisitOverviewLoading = false;
    public visitOverviewError: string | null = null;
    public isVisiting = false;
    public visitError: string | null = null;

    private generation = 0;
    private visitOverviewRequestId = 0;
    private initTask: InitTask | null = null;
    private visitOverviewTask: VisitOverviewTask | null = null;
    private visitTask: Promise<MembershipType> | null = null;

    public constructor() {
        makeAutoObservable<this, ClientStorePrivateKey>(this, {
            generation: false,
            visitOverviewRequestId: false,
            initTask: false,
            visitOverviewTask: false,
            visitTask: false,
        }, { autoBind: true });
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
            if (this.initTask?.generation === generation) {
                this.initTask = null;
            }
        });

        this.initTask = { generation, promise };

        return promise;
    }

    public loadVisitOverview(): Promise<void> {
        if (!authStore.isAuth) {
            this.resetVisitState();
            return Promise.resolve();
        }

        const generation = this.generation;

        if (this.visitOverviewTask?.generation === generation) {
            return this.visitOverviewTask.promise;
        }

        const requestId = ++this.visitOverviewRequestId;
        const promise = this.loadVisitOverviewInternal(generation, requestId).finally(() => {
            if (this.visitOverviewTask?.requestId === requestId) {
                this.visitOverviewTask = null;
            }
        });

        this.visitOverviewTask = { generation, requestId, promise };

        return promise;
    }

    public visit(): Promise<MembershipType> {
        if (this.visitTask !== null) {
            return this.visitTask;
        }

        runInAction(() => {
            this.isVisiting = true;
            this.visitError = null;
        });

        const task = this.visitInternal().finally(() => {
            if (this.visitTask === task) {
                this.visitTask = null;

                runInAction(() => {
                    this.isVisiting = false;
                });
            }
        });

        this.visitTask = task;

        return task;
    }

    public async update(payload: ClientEditType): Promise<void> {
        const generation = this.generation;

        runInAction(() => {
            this.isLoading = true;
            this.error = null;
        });

        try {
            const client = await updateCurrentClient(payload);

            if (generation !== this.generation || !authStore.isAuth) {
                return;
            }

            runInAction(() => {
                this.client = client;
            });
        } catch (error: unknown) {
            if (generation === this.generation && authStore.isAuth) {
                runInAction(() => {
                    this.error = getErrorMessage(error, "Failed to update the profile.");
                });
            }

            throw error;
        } finally {
            if (generation === this.generation) {
                runInAction(() => {
                    this.isLoading = false;
                });
            }
        }
    }

    public async delete(): Promise<void> {
        const generation = this.generation;

        runInAction(() => {
            this.isLoading = true;
            this.error = null;
        });

        try {
            await deleteCurrentClient();

            if (generation === this.generation) {
                await authStore.logout();
            }
        } catch (error: unknown) {
            if (generation === this.generation && authStore.isAuth) {
                runInAction(() => {
                    this.error = getErrorMessage(error, "Failed to delete the profile.");
                });
            }

            throw error;
        } finally {
            if (generation === this.generation) {
                runInAction(() => {
                    this.isLoading = false;
                });
            }
        }
    }

    public async activate(payload: ClientActivateType): Promise<void> {
        const generation = this.generation;

        runInAction(() => {
            this.isLoading = true;
            this.error = null;
        });

        try {
            const response = await apiPost<ApiItemResponse<ClientType>, ClientActivateType>(
                "/clients/activate/",
                payload,
                { skipAuthRefresh: true },
            );

            if (generation === this.generation) {
                runInAction(() => {
                    this.client = response.data;
                });
            }
        } catch (error: unknown) {
            if (generation === this.generation) {
                runInAction(() => {
                    this.error = getErrorMessage(error, "Failed to activate the profile.");
                });
            }

            throw error;
        } finally {
            if (generation === this.generation) {
                runInAction(() => {
                    this.isLoading = false;
                });
            }
        }
    }

    public reset(): void {
        this.generation += 1;
        this.visitOverviewRequestId += 1;
        this.initTask = null;
        this.visitOverviewTask = null;
        this.visitTask = null;
        this.client = null;
        this.isLoading = false;
        this.error = null;
        this.resetVisitState();
    }

    private resetVisitState(): void {
        this.activeMembership = null;
        this.isVisitOverviewLoading = false;
        this.visitOverviewError = null;
        this.isVisiting = false;
        this.visitError = null;
    }

    private async visitInternal(): Promise<MembershipType> {
        const generation = this.generation;
        const hadActiveMembership = this.activeMembership !== null;

        try {
            const membership = await registerVisit();

            if (generation === this.generation && authStore.isAuth) {
                this.initTask = null;
                this.visitOverviewTask = null;

                await Promise.all([
                    this.init(),
                    this.loadVisitOverview(),
                ]);
            }

            return membership;
        } catch (error: unknown) {
            if (generation === this.generation && authStore.isAuth) {
                runInAction(() => {
                    this.visitError = getVisitErrorMessage(error, hadActiveMembership);
                });

                this.visitOverviewTask = null;
                await this.loadVisitOverview();
            }

            throw error;
        }
    }

    private async load(generation: number): Promise<void> {
        runInAction(() => {
            this.isLoading = true;
            this.error = null;
        });

        try {
            const client = await getCurrentClient();

            if (generation !== this.generation || !authStore.isAuth) {
                return;
            }

            runInAction(() => {
                this.client = client;
            });
        } catch (error: unknown) {
            if (generation === this.generation && authStore.isAuth) {
                runInAction(() => {
                    this.client = null;
                    this.error = getErrorMessage(error, "Failed to load the profile.");
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

    private async loadVisitOverviewInternal(
        generation: number,
        requestId: number,
    ): Promise<void> {
        runInAction(() => {
            this.isVisitOverviewLoading = true;
            this.visitOverviewError = null;
        });

        try {
            const response = await getAllMemberships({
                status: MembershipStatusEnum.ACTIVE,
                sort: "endDate:ASC",
                limit: 1,
            });

            if (
                generation === this.generation
                && requestId === this.visitOverviewRequestId
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.activeMembership = response.data[0] ?? null;
                });
            }
        } catch (error: unknown) {
            if (
                generation === this.generation
                && requestId === this.visitOverviewRequestId
                && authStore.isAuth
            ) {
                runInAction(() => {
                    this.activeMembership = null;
                    this.visitOverviewError = getErrorMessage(
                        error,
                        "Failed to load the active membership.",
                    );
                });
            }
        } finally {
            if (
                generation === this.generation
                && requestId === this.visitOverviewRequestId
            ) {
                runInAction(() => {
                    this.isVisitOverviewLoading = false;
                });
            }
        }
    }
}

export const clientStore = new ClientStore();
