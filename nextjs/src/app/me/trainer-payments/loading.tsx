import LoadingState from "@/shared/ui/LoadingState";

const TrainerPaymentsLoading = () => {
    return (
        <main className="px-6 pt-32 pb-20">
            <LoadingState
                title="Opening trainer payments..."
                description="We are preparing trainer payment history."
            />
        </main>
    );
};

export default TrainerPaymentsLoading;
