import TrainerData from "@/types/trainer.type";

type Props = {
    id: number,
    trainer: TrainerData | null,
    amount: string,
    category: string,
};

const Payment = ({ id, trainer, amount, category }: Props) => {
    return (
        <><p>{id}</p>
            <p>{trainer?.firstName}</p>
            <p>{amount}</p>
            <p>{category}</p>
        </>
    );
}

export default Payment;