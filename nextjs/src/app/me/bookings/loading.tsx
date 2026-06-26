import LoadingState from "@/shared/ui/LoadingState";

const BookingsLoading = () => {
    return (
        <main className="px-6 pt-32 pb-20">
            <LoadingState
                title="Opening bookings..."
                description="We are preparing your booking history."
            />
        </main>
    );
};

export default BookingsLoading;
