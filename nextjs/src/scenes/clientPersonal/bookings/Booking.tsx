import { motion } from "framer-motion";
import {useStore} from "@/store/StoreProvider";
import {observer} from "mobx-react-lite";
import {notify} from "@/lib/notify";
import { BookingStatusEnum } from "@/types/booking/bookings-status.enum";
import {getErrorMessage} from "@/lib/getErrorMessage";

const statusColorMap: Record<string, string> = {
    scheduled: "bg-blue-100 text-blue-800",
    pending: "bg-yellow-100 text-yellow-800",
    completed: "bg-green-100 text-green-800",
    canceled_by_client: "bg-red-100 text-red-800",
    canceled_by_trainer: "bg-red-100 text-red-800",
    canceled_by_system: "bg-gray-100 text-gray-800",
    canceled_payment_failed: "bg-red-200 text-red-900",
};

type Props = {
    id: number,
    trainerId: number,
    bookedAt: string,
    date: string,
    durationMinutes: number,
    startTime: string,
    status: BookingStatusEnum,
}

const Booking = observer(({ id, date, durationMinutes, startTime, status }: Props) => {
    const { bookingStore } = useStore();

    const handleDelete = async () => {
        if (!confirm("Cancel this training?")) return;

        const toastId = notify.loading("Cancelling booking...");

        try {
            await bookingStore.cancel(id);

            notify.success(
                "Booking cancelled",
                "Your training has been removed",
                toastId
            );
        } catch (error: unknown) {
            notify.error(
                "Cancellation failed",
                getErrorMessage(error),
                toastId,
            );
        }
    };

    const badgeColors = statusColorMap[status] || "bg-gray-100 text-gray-800";

    const isScheduled = status === 'scheduled';

    return (
        <motion.div
            whileHover={{ scale: 1.02 }}
            className="flex flex-col"
        >
            <motion.div
                className={`border border-gray-50 p-4 flex justify-between items-center ${isScheduled ? 'rounded-t-xl' : 'rounded-xl'}`}
            >
                <div>
                    <p className="font-semibold">{date}</p>
                    <p className="text-sm">
                        {startTime} • {durationMinutes} min
                    </p>
                </div>

                <span className={`text-sm px-3 py-1 rounded-full ${badgeColors}`}>
                    {status.replace(/_/g, ' ')}
                </span>
            </motion.div>

            {isScheduled && (
                <button
                    className="rounded-b-2xl cursor-pointer bg-primary-300 px-10 hover:bg-primary-500 hover:text-white"
                    onClick={handleDelete}
                >
                    Cancel
                </button>
            )}
        </motion.div>
    );
});

export default Booking;
