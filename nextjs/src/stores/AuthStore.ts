import { makeAutoObservable } from "mobx";

class AuthStore {
    token: string | null = null;
    isAuth = false;
    isLoading = true;

    constructor() {
        makeAutoObservable(this);
    }

}