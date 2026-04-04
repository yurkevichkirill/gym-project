import "@/app/globals.css"
import TrainerPersonal from "@/scenes/trainer";
import {BookingProvider} from "@/context/booking.context";

type Props = {
    params: Promise<{ id: string }>
}

const TrainerPage = async ({ params }: Props) => {
    const { id } = await params;
    return (
        <BookingProvider>
            <TrainerPersonal id={id} />
        </BookingProvider>
    );
}

export default TrainerPage;