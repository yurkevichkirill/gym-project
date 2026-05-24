import { motion } from "framer-motion";
import {useStore} from "@/store/StoreProvider";
import {observer} from "mobx-react-lite";
import {notify} from "@/lib/notify";

type Props = {
    id: number,
    trainerId: number,
    bookedAt: string,
    date: string,
    durationMinutes: number,
    startTime: string,
    status: string,
}

const Booking = observer(({ id, trainerId, bookedAt, date, durationMinutes, startTime, status }: Props) => {
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

        } catch (error: any) {
            notify.error(
                "Cancellation failed",
                error?.message || "Something went wrong",
                toastId,
            );
        }
    }

    return (
        <motion.div
            whileHover={{ scale: 1.02 }}
            className="flex flex-col"
        >
            <motion.div
                className="border border-gray-50 rounded-t-xl p-4 flex justify-between items-center"
            >
                <div>
                    <p className="font-semibold">{date}</p>
                    <p className="text-sm">
                        {startTime} • {durationMinutes} min
                    </p>
                </div>

                <span className="text-sm px-3 py-1 rounded-full bg-primary-100">
                    {status}
                </span>
            </motion.div>
            <button
                className="rounded-b-2xl cursor-pointer bg-primary-300 px-10 hover:bg-primary-500 hover:text-white"
                onClick={handleDelete}
            >
                Delete
            </button>
        </motion.div>
    );
});

export default Booking;