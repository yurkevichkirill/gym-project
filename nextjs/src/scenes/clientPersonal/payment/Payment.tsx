import TrainerData from "@/types/trainer/public/trainer.type";
import {
    ArrowUturnUpIcon
} from "@heroicons/react/16/solid";

type Props = {
    id: number,
    trainer: TrainerData | null,
    amount: number,
    category: string,
    isRefund: boolean,
};

const Payment = ({ id, trainer, amount, category, isRefund }: Props) => {
    return (
        <div className={`flex justify-between items-center rounded-xl p-4
        ${isRefund ? "bg-gray-100" : (category === 'trainer' ? "bg-primary-100" : "bg-secondary-400")} 
        `}>
            <p>{category}</p>
            <div className="flex gap-4 items-center">
                <p className="font-semibold">
                    {isRefund && <ArrowUturnUpIcon className="w-4 h-4" />}
                </p>
                <p className="font-semibold">
                    {amount} $
                </p>
            </div>
        </div>
    );
}

export default Payment;