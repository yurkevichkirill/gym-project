import LoadingState from "@/shared/ui/LoadingState";

const PaymentLoading = () => {
    return (
        <main className="px-6 pt-32 pb-20">
            <LoadingState
                title="Opening payment..."
                description="We are preparing the payment details."
            />
        </main>
    );
};

export default PaymentLoading;
