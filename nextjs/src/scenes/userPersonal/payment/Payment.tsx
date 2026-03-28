import TrainerData from "@/types/trainer.type";

type Props = {
    id: number,
    trainer: TrainerData | null,
    amount: string,
    category: string,
};

const Payment = ({ id, trainer, amount, category }: Props) => {
    return (
        <div className={`flex justify-between items-center rounded-xl p-4
        ${category === 'trainer' ? "bg-primary-100" : "bg-secondary-400"} 
        `}>
            <p>{category}</p>
            <p className="font-semibold">
                {amount} $
            </p>
        </div>
    );
}

export default Payment;