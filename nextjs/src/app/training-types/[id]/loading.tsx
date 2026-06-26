import LoadingState from "@/shared/ui/LoadingState";

const TrainingTypeLoading = () => {
    return (
        <main className="px-6 pb-20 pt-32">
            <LoadingState
                title="Loading training type..."
                description="We are fetching the training type details."
            />
        </main>
    );
};

export default TrainingTypeLoading;
