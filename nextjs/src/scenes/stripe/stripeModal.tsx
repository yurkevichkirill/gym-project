'use client'

import { Elements, PaymentElement, useStripe, useElements } from "@stripe/react-stripe-js";
import { loadStripe } from "@stripe/stripe-js";
import { useState, FormEvent } from "react";
import { notify } from "@/lib/notify";

const stripePromise = loadStripe(process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY || "");

type ModalProps = {
    clientSecret: string;
    onClose: () => void;
    onSuccess: () => void;
}

const CheckoutForm = ({ onClose, onSuccess }: { onClose: () => void; onSuccess: () => void }) => {
    const stripe = useStripe();
    const elements = useElements();
    const [isProcessing, setIsProcessing] = useState(false);

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();

        if (!stripe || !elements) return;

        setIsProcessing(true);
        const toastId = notify.loading("Validating card and processing payment...");

        const { error } = await stripe.confirmPayment({
            elements,
            confirmParams: {
                return_url: window.location.href, 
            },
            redirect: "if_required"
        });

        if (error) {
            notify.error("Payment failed", error.message || "An error occurred", toastId);
            setIsProcessing(false);
        } else {
            notify.success("Payment successful!", "Your training is booked.", toastId);
            setIsProcessing(false);
            onSuccess();
        }
    };

    return (
        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
            <PaymentElement />
            <div className="flex gap-3 mt-4 justify-end">
                <button
                    type="button"
                    onClick={onClose}
                    className="px-4 py-2 border rounded-md text-gray-600 hover:bg-gray-50 cursor-pointer"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    disabled={isProcessing || !stripe}
                    className="px-6 py-2 bg-primary-500 text-white rounded-md hover:bg-primary-600 disabled:opacity-50 cursor-pointer"
                >
                    {isProcessing ? "Processing..." : "Pay Now"}
                </button>
            </div>
        </form>
    );
};

// Корневой компонент модалки с оберткой Elements
export const StripeModal = ({ clientSecret, onClose, onSuccess }: ModalProps) => {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div className="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl animate-in fade-in zoom-in-95 duration-200">
                <h3 className="text-2xl font-bold mb-4 text-gray-800">Secure Payment</h3>
                <Elements stripe={stripePromise} options={{ clientSecret }}>
                    <CheckoutForm onClose={onClose} onSuccess={onSuccess} />
                </Elements>
            </div>
        </div>
    );
};