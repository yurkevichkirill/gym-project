'use client';

import TrainerProfile from "@/scenes/trainerPersonal/TrainerProfile";
import {
    errorStateClassName,
    loadingStateClassName,
    primaryActionClassName,
} from "@/shared/Section";
import { useStore } from "@/store/StoreProvider";
import { observer } from "mobx-react-lite";
import { useEffect } from "react";

const MyPersonalTrainer = observer(() => {
    const { trainerStore } = useStore();

    useEffect(() => {
        if (
            trainerStore.trainer === null
            && !trainerStore.isLoading
            && trainerStore.error === null
        ) {
            void trainerStore.init();
        }
    }, [trainerStore]);

    if (trainerStore.isLoading && trainerStore.trainer === null) {
        return (
            <div className="bg-gray-20 pt-32 pb-20">
                <div className="mx-auto flex w-11/12 max-w-5xl flex-col gap-6 sm:w-5/6">
                    <div className={loadingStateClassName} role="status" aria-live="polite">
                        Loading trainer profile...
                    </div>
                </div>
            </div>
        );
    }

    if (trainerStore.error !== null && trainerStore.trainer === null) {
        return (
            <div className="bg-gray-20 pt-32 pb-20">
                <div className="mx-auto flex w-11/12 max-w-5xl flex-col gap-6 sm:w-5/6">
                    <div className={errorStateClassName} role="alert">
                        <p className="font-semibold">Unable to load trainer profile.</p>
                        <p className="mt-2 text-sm">{trainerStore.error}</p>
                        <button
                            type="button"
                            className={`mt-4 ${primaryActionClassName}`}
                            disabled={trainerStore.isLoading}
                            onClick={() => void trainerStore.init()}
                        >
                            {trainerStore.isLoading ? "Retrying..." : "Retry"}
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    if (trainerStore.trainer === null) {
        return null;
    }

    return (
        <div className="bg-gray-20 pt-32 pb-20">
            <div className="mx-auto flex w-11/12 max-w-5xl flex-col gap-6 sm:w-5/6">
                <TrainerProfile trainer={trainerStore.trainer} />
            </div>
        </div>
    );
});

export default MyPersonalTrainer;
