import Link from "next/link";
import EmptyState from "@/shared/ui/EmptyState";

const BookingNotFound = () => (
    <main className="px-6 pt-32 pb-20">
        <EmptyState
            title="Booking not found"
            description="The requested booking identifier is invalid or the booking is unavailable."
            action={<Link href="/me/bookings" className="rounded-md bg-secondary-500 px-5 py-2 font-semibold transition hover:bg-primary-500 hover:text-white">Back to bookings</Link>}
        />
    </main>
);

export default BookingNotFound;
