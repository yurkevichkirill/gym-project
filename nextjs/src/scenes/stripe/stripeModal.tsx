'use client'

import { Elements, PaymentElement, useStripe, useElements } from "@stripe/react-stripe-js";
import { loadStripe } from "@stripe/stripe-js";
import { useState, FormEvent } from "react";
import { notify } from "@/lib/notify";
import { getErrorMessage } from "@/lib/getErrorMessage";

const stripePromise = loadStripe(process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY || "");

type ModalProps = {
    clientSecret: string;
    onClose: () => void;
    onSuccess: () => void;
    successTitle?: string;
    successDescription?: string;
}

const CheckoutForm = ({ onClose, onSuccess, successTitle, successDescription }: {
    onClose: () => void;
    onSuccess: () => void;
    successTitle: string;
    successDescription: string;
}) => {
    const stripe = useStripe();
    const elements = useElements();
    const [isProcessing, setIsProcessing] = useState(false);

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();
        if (!stripe || !elements) return;

        setIsProcessing(true);
        const toastId = notify.loading("Processing payment...");

        try {
            const { error, paymentIntent } = await stripe.confirmPayment({
                elements,
                redirect: "if_required",
            });

            if (error) {
                notify.error("Payment failed", error.message || "An error occurred", toastId);
                setIsProcessing(false);

                return;
            }

            if (!paymentIntent) {
                notify.error(
                    "Payment status unavailable",
                    "Stripe did not return a payment status. Please check your payments before trying again.",
                    toastId,
                );
                setIsProcessing(false);

                return;
            }

            if (paymentIntent.status === "succeeded") {
                notify.success(successTitle, successDescription, toastId);
                setIsProcessing(false);
                onSuccess();

                return;
            }

            if (paymentIntent.status === "processing") {
                notify.dismiss(toastId);
                notify.info(
                    "Payment processing",
                    "Stripe is still processing this payment. Its final status will update automatically.",
                );
                setIsProcessing(false);
                onClose();

                return;
            }

            notify.error(
                "Payment incomplete",
                `Stripe returned status: ${paymentIntent.status.replaceAll('_', ' ')}. The payment was not marked as successful.`,
                toastId,
            );
            setIsProcessing(false);
        } catch (error: unknown) {
            notify.error("Payment failed", getErrorMessage(error), toastId);
            setIsProcessing(false);
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

export const StripeModal = ({ clientSecret, onClose, onSuccess, successTitle, successDescription }: ModalProps) => {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div className="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
                <h3 className="text-2xl font-bold mb-4 text-gray-800">Secure Payment</h3>
                <Elements stripe={stripePromise} options={{ clientSecret }}>
                    <CheckoutForm
                        onClose={onClose}
                        onSuccess={onSuccess}
                        successTitle={successTitle || "Payment successful!"}
                        successDescription={successDescription || "Your order has been processed."}
                    />
                </Elements>
            </div>
        </div>
    );
};
