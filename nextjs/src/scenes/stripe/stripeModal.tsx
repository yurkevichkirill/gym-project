'use client'

import { Elements, PaymentElement, useElements, useStripe } from "@stripe/react-stripe-js";
import { loadStripe } from "@stripe/stripe-js";
import { FormEvent, useState } from "react";
import { notify } from "@/lib/notify";
import { getErrorMessage } from "@/lib/getErrorMessage";

const stripePublishableKey = process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY ?? "";
const stripePromise = stripePublishableKey.length > 0 && !stripePublishableKey.includes("placeholder")
    ? loadStripe(stripePublishableKey)
    : Promise.resolve(null);

const STRIPE_RETURN_PARAMS = [
    "payment_intent",
    "payment_intent_client_secret",
    "redirect_status",
] as const;

type ModalProps = {
    clientSecret: string;
    onClose: () => void;
    onSuccess: () => void | Promise<void>;
    successTitle?: string;
    successDescription?: string;
};

const CheckoutForm = ({ onClose, onSuccess, successTitle, successDescription }: {
    onClose: () => void;
    onSuccess: () => void | Promise<void>;
    successTitle: string;
    successDescription: string;
}) => {
    const stripe = useStripe();
    const elements = useElements();
    const [isProcessing, setIsProcessing] = useState(false);

    const handleSubmit = async (event: FormEvent) => {
        event.preventDefault();

        if (!stripe || !elements) {
            return;
        }

        setIsProcessing(true);
        const toastId = notify.loading("Processing payment...");

        try {
            const returnUrl = new URL(window.location.href);
            STRIPE_RETURN_PARAMS.forEach((param) => returnUrl.searchParams.delete(param));

            const { error, paymentIntent } = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: returnUrl.toString(),
                },
                redirect: "if_required",
            });

            if (error) {
                notify.error("Payment failed", error.message || "An error occurred", toastId);

                return;
            }

            if (!paymentIntent) {
                notify.error(
                    "Payment status unavailable",
                    "Stripe did not return a payment status. Please check your payments before trying again.",
                    toastId,
                );

                return;
            }

            if (paymentIntent.status === "succeeded") {
                notify.success(successTitle, successDescription, toastId);
                await onSuccess();

                return;
            }

            if (paymentIntent.status === "processing") {
                notify.dismiss(toastId);
                notify.info(
                    "Payment processing",
                    "Stripe is still processing this payment. Its final status will update after Stripe redirects back.",
                );
                onClose();

                return;
            }

            notify.error(
                "Payment incomplete",
                `Stripe returned status: ${paymentIntent.status.replaceAll("_", " ")}. The payment was not marked as successful.`,
                toastId,
            );
        } catch (error: unknown) {
            notify.error("Payment failed", getErrorMessage(error), toastId);
        } finally {
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
    const isStripeConfigured = stripePublishableKey.length > 0 && !stripePublishableKey.includes("placeholder");

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div className="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
                <h3 className="text-2xl font-bold mb-4 text-gray-800">Secure Payment</h3>
                {!isStripeConfigured ? (
                    <div className="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">
                        Stripe is not configured for this frontend build. Set NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY and rebuild the app.
                    </div>
                ) : (
                    <Elements stripe={stripePromise} options={{ clientSecret }}>
                        <CheckoutForm
                            onClose={onClose}
                            onSuccess={onSuccess}
                            successTitle={successTitle || "Payment successful!"}
                            successDescription={successDescription || "Your order has been processed."}
                        />
                    </Elements>
                )}
            </div>
        </div>
    );
};
