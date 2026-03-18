import "@/app/globals.css"
import TrainerPersonal from "@/scenes/trainer";

type Props = {
    params: Promise<{ id: string }>
}

const TrainerPage = async ({ params }: Props) => {
    const { id } = await params;
    return <TrainerPersonal id={id} />
}

export default TrainerPage;