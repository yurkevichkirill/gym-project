import LoadingState from "@/shared/ui/LoadingState";

const BookingDetailsLoading = () => {
    return (
        <main className="px-6 pt-32 pb-20">
            <LoadingState
                title="Opening booking..."
                description="We are preparing the booking details."
            />
        </main>
    );
};

export default BookingDetailsLoading;
