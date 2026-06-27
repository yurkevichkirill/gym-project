import LoadingState from "@/shared/ui/LoadingState";

const TrainerPaymentLoading = () => {
    return (
        <main className="px-6 pt-32 pb-20">
            <LoadingState
                title="Opening trainer payment..."
                description="We are preparing payment details."
            />
        </main>
    );
};

export default TrainerPaymentLoading;
