import { PaymentCategoryEnum } from "@/types/payment/payment-category.enum";
import { PaymentMethodEnum } from "@/types/payment/payment-method.enum";
import { PaymentStatusEnum } from "@/types/payment/payment-status.enum";
import TrainerData from "@/types/trainer/public/trainer.type";
import { 
    ArrowUturnUpIcon, 
    UserIcon, 
    TicketIcon, 
    WalletIcon, 
    CreditCardIcon 
} from "@heroicons/react/24/outline";

type Props = {
    id: number;
    trainer: TrainerData | null;
    amount: number;
    currency: string;
    category: string;
    status: string;
    method: string;
    isRefund: boolean;
    createdAt: string;
};

const statusStyleMap: Record<string, string> = {
    [PaymentStatusEnum.SUCCEEDED]: "bg-emerald-100 text-emerald-800",
    [PaymentStatusEnum.PENDING]: "bg-amber-100 text-amber-800",
    [PaymentStatusEnum.FAILED]: "bg-rose-100 text-rose-800",
    [PaymentStatusEnum.CANCELED]: "bg-gray-100 text-gray-800",
    [PaymentStatusEnum.REFUNDED]: "bg-blue-100 text-blue-800",
};

const getCategoryDetails = (category: string, trainer: TrainerData | null) => {
    switch (category) {
        case PaymentCategoryEnum.MEMBERSHIP:
            return { 
                icon: <TicketIcon className="w-5 h-5 text-indigo-600" />, 
                title: "Membership", 
                bg: "bg-indigo-50" 
            };
        case PaymentCategoryEnum.TRAINER:
            return { 
                icon: <UserIcon className="w-5 h-5 text-emerald-600" />, 
                title: trainer ? trainer.firstName + " " + trainer.lastName : "Trainer Session", 
                bg: "bg-emerald-50" 
            };
        case PaymentCategoryEnum.BALANCE_TOP_UP:
            return { 
                icon: <WalletIcon className="w-5 h-5 text-amber-600" />, 
                title: "Balance Top-Up", 
                bg: "bg-amber-50" 
            };
        default:
            return { 
                icon: <WalletIcon className="w-5 h-5 text-gray-600" />, 
                title: "Payment", 
                bg: "bg-gray-50" 
            };
    }
};

const Payment = ({ trainer, amount, currency, category, status, method, isRefund, createdAt: createAt }: Props) => {
    const statusColors = statusStyleMap[status] || "bg-gray-100 text-gray-800";
    const { icon, title, bg } = getCategoryDetails(category, trainer);
    
    const formattedDate = new Date(createAt).toLocaleDateString('en-US', { 
        day: 'numeric', 
        month: 'short', 
        year: 'numeric' 
    });

    const isPositive = isRefund || category === PaymentCategoryEnum.BALANCE_TOP_UP;

    return (
        <div className={`border border-gray-100 rounded-2xl p-4 flex items-center justify-between transition-all hover:shadow-sm ${isRefund ? 'border-blue-100 bg-blue-50/30' : ''}`}>
            <div className="flex gap-4 items-center">
                <div className={`p-3 rounded-xl ${bg}`}>
                    {icon}
                </div>
                
                <div className="flex flex-col">
                    <p className="font-semibold text-gray-900">{title}</p>
                    <div className="flex items-center gap-2 text-sm text-gray-500 mt-0.5">
                        <span>{formattedDate}</span>
                        <span className="w-1 h-1 rounded-full bg-gray-300"></span>
                        <span className="flex items-center gap-1 capitalize">
                            {method === PaymentMethodEnum.CARD ? (
                                <CreditCardIcon className="w-3.5 h-3.5" />
                            ) : (
                                <WalletIcon className="w-3.5 h-3.5" />
                            )}
                            {method}
                        </span>
                    </div>
                </div>
            </div>

            <div className="flex flex-col items-end gap-2">
                <div className={`flex items-center gap-1.5 font-bold ${isPositive ? 'text-emerald-600' : 'text-gray-900'}`}>
                    {isRefund && <ArrowUturnUpIcon className="w-4 h-4 text-blue-600 stroke-2" />}
                    {isPositive ? '+' : '-'}{amount} {currency.toUpperCase()}
                </div>
                
                <span className={`text-sm px-3 py-1 rounded-full ${statusColors}`}>
                    {status}
                </span>
            </div>
        </div>
    );
}

export default Payment;