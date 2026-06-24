import {makeAutoObservable, runInAction} from "mobx";
import {
    CurrentUser,
    LoginRequest,
} from "@/types/auth.type";
import {apiPost, ApiClientError, beginAuthLogout, finishAuthLogout} from "@/lib/apiClient";
import {ApiItemResponse} from "@/types/api-item-response.type";
import ClientType from "@/types/client/client.type";
import {ClientRegisterRequest} from "@/types/client/client-register-request.type";
import {
    getCurrentUser,
    login as loginRequest,
    logout as logoutRequest,
} from "@/api/auth/auth.api";
import {getErrorMessage} from "@/lib/getErrorMessage";

export const AuthStatus = {
    INITIAL: "initial",
    LOADING: "loading",
    AUTHENTICATED: "authenticated",
    UNAUTHENTICATED: "unauthenticated",
    ERROR: "error",
} as const;

export type AuthStatus = typeof AuthStatus[keyof typeof AuthStatus];

type CheckAuthTask = {
    generation: number;
    promise: Promise<CurrentUser | null>;
};

class AuthStore {
    public user: CurrentUser | null = null;
    public status: AuthStatus = AuthStatus.INITIAL;
    public error: string | null = null;
    public isMutationLoading = false;

    private operationGeneration = 0;
    private checkAuthTask: CheckAuthTask | null = null;
    private isLoggingOut = false;
    private resetUserStores: () => void = () => undefined;

    public constructor() {
        makeAutoObservable(this, {
            operationGeneration: false,
            checkAuthTask: false,
            isLoggingOut: false,
            resetUserStores: false,
        }, {autoBind: true});
    }

    public get isAuth(): boolean {
        return this.user !== null
            && this.status !== AuthStatus.UNAUTHENTICATED
            && this.status !== AuthStatus.ERROR;
    }

    public get isLoading(): boolean {
        return this.status === AuthStatus.LOADING || this.isMutationLoading;
    }

    public get isInitialized(): boolean {
        return this.status !== AuthStatus.INITIAL;
    }

    public configureUserStoresReset(resetUserStores: () => void): void {
        this.resetUserStores = resetUserStores;
    }

    public async login(payload: LoginRequest): Promise<CurrentUser> {
        if (this.isLoggingOut) {
            throw new Error("Logout is still in progress.");
        }

        const generation = this.startOperation();

        runInAction(() => {
            this.status = AuthStatus.LOADING;
            this.error = null;
        });

        try {
            await loginRequest(payload);

            if (generation !== this.operationGeneration || this.isLoggingOut) {
                throw new Error("Login was cancelled.");
            }

            const user = await this.checkAuth();
            if (user === null || generation !== this.operationGeneration) {
                throw new Error("Unable to load the authenticated user.");
            }

            return user;
        } catch (error: unknown) {
            if (generation === this.operationGeneration && !this.isLoggingOut) {
                const isUnauthorized = error instanceof ApiClientError
                    && (error.status === 401 || error.status === 403);

                runInAction(() => {
                    this.user = null;
                    this.status = isUnauthorized
                        ? AuthStatus.UNAUTHENTICATED
                        : AuthStatus.ERROR;
                    this.error = isUnauthorized ? null : getErrorMessage(error);
                });
                this.resetUserStores();
            }

            throw error;
        }
    }

    public async register(payload: ClientRegisterRequest): Promise<ApiItemResponse<ClientType>> {
        runInAction(() => {
            this.isMutationLoading = true;
        });

        try {
            return await apiPost<ApiItemResponse<ClientType>, ClientRegisterRequest>(
                "/client/registration/",
                {
                    ...payload,
                    age: Number(payload.age),
                },
                {skipAuthRefresh: true},
            );
        } finally {
            runInAction(() => {
                this.isMutationLoading = false;
            });
        }
    }

    public checkAuth(): Promise<CurrentUser | null> {
        if (this.isLoggingOut) {
            return Promise.resolve(null);
        }

        const generation = this.operationGeneration;
        if (this.checkAuthTask?.generation === generation) {
            return this.checkAuthTask.promise;
        }

        const promise = this.loadCurrentUser(generation).finally(() => {
            if (this.checkAuthTask?.promise === promise) {
                this.checkAuthTask = null;
            }
        });

        this.checkAuthTask = {generation, promise};

        return promise;
    }

    public async logout(): Promise<void> {
        const generation = this.startOperation();
        this.isLoggingOut = true;

        runInAction(() => {
            this.user = null;
            this.status = AuthStatus.LOADING;
            this.error = null;
        });
        this.resetUserStores();

        await beginAuthLogout();

        let logoutError: unknown = null;

        try {
            await logoutRequest();
        } catch (error: unknown) {
            logoutError = error;
        } finally {
            finishAuthLogout();
            this.isLoggingOut = false;

            if (generation === this.operationGeneration) {
                runInAction(() => {
                    this.user = null;
                    this.status = AuthStatus.UNAUTHENTICATED;
                    this.error = null;
                });
            }
        }

        if (logoutError !== null) {
            throw logoutError;
        }
    }

    private startOperation(): number {
        this.operationGeneration += 1;
        this.checkAuthTask = null;

        return this.operationGeneration;
    }

    private async loadCurrentUser(generation: number): Promise<CurrentUser | null> {
        runInAction(() => {
            this.status = AuthStatus.LOADING;
            this.error = null;
        });

        try {
            const user = await getCurrentUser();

            if (generation !== this.operationGeneration || this.isLoggingOut) {
                return null;
            }

            runInAction(() => {
                this.user = user;
                this.status = AuthStatus.AUTHENTICATED;
                this.error = null;
            });

            return user;
        } catch (error: unknown) {
            if (generation !== this.operationGeneration || this.isLoggingOut) {
                return null;
            }

            const isUnauthorized = error instanceof ApiClientError
                && (error.status === 401 || error.status === 403);

            runInAction(() => {
                this.user = null;
                this.status = isUnauthorized
                    ? AuthStatus.UNAUTHENTICATED
                    : AuthStatus.ERROR;
                this.error = isUnauthorized ? null : getErrorMessage(error);
            });
            this.resetUserStores();

            return null;
        }
    }
}

export const authStore = new AuthStore();
