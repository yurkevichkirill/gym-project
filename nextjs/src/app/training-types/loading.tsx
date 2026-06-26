import LoadingState from "@/shared/ui/LoadingState";

const TrainingTypesLoading = () => {
    return (
        <main className="px-6 pb-20 pt-32">
            <LoadingState
                title="Loading training types..."
                description="We are preparing the training catalog."
            />
        </main>
    );
};

export default TrainingTypesLoading;
