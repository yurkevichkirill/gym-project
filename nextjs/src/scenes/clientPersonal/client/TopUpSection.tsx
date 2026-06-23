'use client'

import { useState } from "react";
import { notify } from "@/lib/notify";
import { createStripeIntent } from "@/api/client/payments.api";
import { useStore } from "@/store/StoreProvider";
import { topUpBalance } from "@/api/client/client.api";
import { StripeModal } from "@/scenes/stripe/stripeModal";
import { getErrorMessage } from "@/lib/getErrorMessage";

export const TopUpSection = () => {
    const { authStore } = useStore();

    const [isOpen, setIsOpen] = useState(false);
    const [amount, setAmount] = useState<string>("20");
    const [isLoading, setIsLoading] = useState(false);
    const [stripeClientSecret, setStripeClientSecret] = useState<string | null>(null);

    const handleInitTopUp = async (e: React.FormEvent) => {
        e.preventDefault();
        const dollarAmount = parseFloat(amount);

        if (isNaN(dollarAmount) || dollarAmount <= 0) {
            notify.error("Invalid amount", "Please enter a valid sum to top up.");
            return;
        }

        setIsLoading(true);
        const toastId = notify.loading("Preparing transaction...");

        try {
            const cents = Math.round(dollarAmount * 100);
            const payment = await topUpBalance({ amount: cents });
            const clientSecret = await createStripeIntent(payment.id);

            setStripeClientSecret(clientSecret);
            setIsOpen(false);
            notify.dismiss(toastId);
        } catch (error: unknown) {
            notify.error("Failed", getErrorMessage(error), toastId);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <>
            <button
                onClick={() => setIsOpen(true)}
                className="
                    flex-1
                    rounded-bl-2xl
                    cursor-pointer
                    bg-gray-900
                    px-10
                    hover:bg-primary-500
                    text-white
                    transition-colors
                    duration-200
                "
            >
                Top Up
            </button>

            {isOpen && (
                <div className="fixed inset-0 z-40 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                    <div className="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl border border-gray-100 animate-in fade-in zoom-in-95 duration-150">
                        <h4 className="text-xl font-bold text-gray-800 mb-2">Refill your wallet</h4>
                        <p className="text-sm text-gray-500 mb-4">Enter the amount you want to deposit into your account balance.</p>

                        <form onSubmit={handleInitTopUp} className="flex flex-col gap-4">
                            <div className="grid grid-cols-4 gap-2">
                                {["10", "20", "50", "100"].map((preset) => (
                                    <button
                                        key={preset}
                                        type="button"
                                        onClick={() => setAmount(preset)}
                                        className={`py-2 text-sm font-semibold rounded-lg border transition-all cursor-pointer
                                            ${amount === preset
                                                ? "bg-primary-500 border-primary-500 text-white shadow-md"
                                                : "border-gray-200 text-gray-700 hover:bg-gray-50"}`}
                                    >
                                        ${preset}
                                    </button>
                                ))}
                            </div>

                            <div className="relative mt-2">
                                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold">$</span>
                                <input
                                    type="number"
                                    min="1"
                                    step="any"
                                    value={amount}
                                    onChange={(e) => setAmount(e.target.value)}
                                    placeholder="Custom amount"
                                    className="w-full pl-8 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 font-semibold text-gray-900"
                                    required
                                />
                            </div>

                            <div className="flex gap-2 mt-2 justify-end">
                                <button
                                    type="button"
                                    onClick={() => setIsOpen(false)}
                                    className="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 cursor-pointer"
                                >
                                    Close
                                </button>
                                <button
                                    type="submit"
                                    disabled={isLoading}
                                    className="px-5 py-2 text-sm font-bold text-white bg-primary-500 rounded-lg hover:bg-primary-600 transition disabled:opacity-50 cursor-pointer"
                                >
                                    {isLoading ? "Loading..." : "Proceed to Payment"}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {stripeClientSecret && (
                <StripeModal
                    clientSecret={stripeClientSecret}
                    onClose={() => setStripeClientSecret(null)}
                    onSuccess={() => {
                        setStripeClientSecret(null);

                        setTimeout(() => {
                            void authStore.checkAuth();
                        }, 1500);
                    }}
                    successTitle="Balance Topped Up!"
                    successDescription="Transaction successful! Your balance will automatically update in a few seconds."
                />
            )}
        </>
    );
};
