'use client';

import TrainerProfile from "@/scenes/trainerPersonal/TrainerProfile";
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
        return <div className="pt-32 text-center">Loading trainer profile...</div>;
    }

    if (trainerStore.error !== null && trainerStore.trainer === null) {
        return (
            <div className="pt-32 text-center">
                <p role="alert">{trainerStore.error}</p>
                <button
                    type="button"
                    className="mt-4 rounded-md bg-secondary-500 px-5 py-2 disabled:opacity-50"
                    disabled={trainerStore.isLoading}
                    onClick={() => void trainerStore.init()}
                >
                    Retry
                </button>
            </div>
        );
    }

    if (trainerStore.trainer === null) {
        return null;
    }

    return (
        <div className="pt-32 pb-20">
            <div className="mx-auto w-5/6 max-w-6xl">
                <TrainerProfile trainer={trainerStore.trainer} />
            </div>
        </div>
    );
});

export default MyPersonalTrainer;
