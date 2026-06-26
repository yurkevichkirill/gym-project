import { notFound } from "next/navigation";
import BookingDetails from "@/scenes/clientPersonal/bookings/BookingDetails";

type BookingPageProps = {
    params: Promise<{ id: string }>;
};

const BookingPage = async ({ params }: BookingPageProps) => {
    const { id } = await params;

    if (!/^\d+$/.test(id)) {
        notFound();
    }

    const bookingId = Number(id);

    if (!Number.isSafeInteger(bookingId) || bookingId <= 0) {
        notFound();
    }

    return (
        <main className="px-6 pt-32 pb-20">
            <BookingDetails bookingId={bookingId} />
        </main>
    );
};

export default BookingPage;
