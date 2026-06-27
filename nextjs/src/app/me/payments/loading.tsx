import LoadingState from "@/shared/ui/LoadingState";

const PaymentsLoading = () => {
    return (
        <main className="px-6 pt-32 pb-20">
            <LoadingState
                title="Opening payments..."
                description="We are preparing your payment history."
            />
        </main>
    );
};

export default PaymentsLoading;
