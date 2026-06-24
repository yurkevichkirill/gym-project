'use client';

import { useForm } from "react-hook-form";
import { useStore } from "@/store/StoreProvider";
import { createPortal } from "react-dom";
import { AnimatePresence, motion } from "framer-motion";
import { EyeIcon, EyeSlashIcon } from "@heroicons/react/24/solid";
import { useState } from "react";
import { observer } from "mobx-react-lite";
import { notify } from "@/lib/notify";
import { ApiClientError } from "@/lib/apiClient";
import { getErrorMessage } from "@/lib/getErrorMessage";

interface RegisterFormData {
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
    age: number;
    password: string;
}

interface RegisterModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSwitchToLogin?: () => void;
}

const registerFieldNames = new Set<keyof RegisterFormData>([
    "firstName",
    "lastName",
    "email",
    "phone",
    "age",
    "password",
]);

const isRegisterField = (value: unknown): value is keyof RegisterFormData => {
    return typeof value === "string"
        && registerFieldNames.has(value as keyof RegisterFormData);
};

const RegisterModal = observer(({ isOpen, onClose, onSwitchToLogin }: RegisterModalProps) => {
    const { authStore } = useStore();
    const [passVisible, setPassVisible] = useState(false);

    const {
        register,
        handleSubmit,
        formState: { errors, isSubmitting },
        reset,
        setError
    } = useForm<RegisterFormData>({
        mode: "onChange",
    });

    const onSubmit = async (data: RegisterFormData) => {
        const toastId = notify.loading("Creating your account...");

        try {
            await authStore.register(data);
            reset();
            onClose();

            notify.success(
                "Welcome!",
                "Account created successfully. You can now log in.",
                toastId,
            );

            if (onSwitchToLogin) onSwitchToLogin();
        } catch (error: unknown) {
            let fieldErrorWasApplied = false;

            if (error instanceof ApiClientError && error.status === 422) {
                for (const violation of error.payload.violations ?? []) {
                    if (!isRegisterField(violation.propertyPath)) {
                        continue;
                    }

                    setError(violation.propertyPath, {
                        type: "server",
                        message: violation.title || violation.message || "Invalid value.",
                    });
                    fieldErrorWasApplied = true;
                }
            }

            if (fieldErrorWasApplied) {
                notify.dismiss(toastId);
                return;
            }

            notify.error(
                "Registration failed",
                getErrorMessage(error),
                toastId,
            );
        }
    };

    const isSubmitDisabled = isSubmitting
        || authStore.isLoading
        || Object.keys(errors).length > 0;

    return createPortal(
        <AnimatePresence>
            {isOpen && (
                <motion.div
                    className="fixed inset-0 bg-black/50 flex justify-center items-center z-50"
                    onClick={onClose}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                >
                    <motion.div
                        className="bg-white p-6 rounded-xl w-[450px] relative max-h-[90vh] overflow-y-auto"
                        onClick={(e) => e.stopPropagation()}
                        initial={{ opacity: 0, y: 50 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.2, duration: 0.5 }}
                    >
                        <button
                            onClick={onClose}
                            className="absolute top-2 right-2 text-gray-500 cursor-pointer"
                        >
                            ✕
                        </button>
                        <h2 className="text-xl font-bold mb-1">Become a Member</h2>
                        <div className="h-[1px] bg-gray-100 mb-2"></div>

                        <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-3">

                            {/* First Name & Last Name */}
                            <div className="grid grid-cols-2 gap-2">
                                <div className={`border rounded ${errors.firstName ? "border-primary-500" : "border-secondary-500"}`}>
                                    <input
                                        type="text"
                                        placeholder="First Name"
                                        {...register("firstName", { required: "First name is required." })}
                                        className="m-2 outline-none w-full bg-transparent"
                                    />
                                </div>
                                <div className={`border rounded ${errors.lastName ? "border-primary-500" : "border-secondary-500"}`}>
                                    <input
                                        type="text"
                                        placeholder="Last Name"
                                        {...register("lastName", { required: "Last name is required." })}
                                        className="m-2 outline-none w-full bg-transparent"
                                    />
                                </div>
                            </div>
                            {(errors.firstName || errors.lastName) && (
                                <p className="mt-1 text-primary-500">
                                    {errors.firstName?.message || errors.lastName?.message}
                                </p>
                            )}

                            {/* Email */}
                            <div className={`border rounded ${errors.email ? "border-primary-500" : "border-secondary-500"}`}>
                                <input
                                    type="text"
                                    placeholder="Email"
                                    {...register("email", {
                                        required: "Email is required.",
                                        pattern: {
                                            value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                                            message: "Invalid email address."
                                        }
                                    })}
                                    className="m-2 outline-none w-full bg-transparent"
                                />
                            </div>
                            {errors.email && (
                                <p className="mt-1 text-primary-500">{errors.email.message}</p>
                            )}

                            {/* Phone & Age */}
                            <div className="grid grid-cols-3 gap-2">
                                <div className={`border rounded col-span-2 ${errors.phone ? "border-primary-500" : "border-secondary-500"}`}>
                                    <input
                                        type="text"
                                        placeholder="Phone (e.g. +1234567)"
                                        {...register("phone", {
                                            required: "Phone is required.",
                                            pattern: {
                                                value: /^\+?[1-9]\d{4,14}$/,
                                                message: "International format required (e.g. +123456789)."
                                            }
                                        })}
                                        className="m-2 outline-none w-full bg-transparent"
                                    />
                                </div>
                                <div className={`border rounded ${errors.age ? "border-primary-500" : "border-secondary-500"}`}>
                                    <input
                                        type="number"
                                        placeholder="Age"
                                        {...register("age", {
                                            required: "Required.",
                                            valueAsNumber: true,
                                            min: { value: 1, message: "Must be positive." }
                                        })}
                                        className="m-2 outline-none w-full bg-transparent"
                                    />
                                </div>
                            </div>
                            {(errors.phone || errors.age) && (
                                <p className="mt-1 text-primary-500">
                                    {errors.phone?.message || errors.age?.message}
                                </p>
                            )}

                            {/* Password */}
                            <div className={`border rounded flex items-center justify-between ${errors.password ? "border-primary-500" : "border-secondary-500"}`}>
                                <input
                                    type={passVisible ? "text" : "password"}
                                    placeholder="Password"
                                    {...register("password", {
                                        required: "Password is required.",
                                        minLength: { value: 8, message: "Password should be at least 8 chars long." },
                                        maxLength: { value: 100, message: "Max length is 100 char." }
                                    })}
                                    className="m-2 outline-none w-full bg-transparent"
                                />
                                <button
                                    onClick={() => setPassVisible(!passVisible)}
                                    className="mr-2 align-middle"
                                    type="button"
                                >
                                    {passVisible ?
                                        <EyeIcon className="h-5 w-5 opacity-70"/> :
                                        <EyeSlashIcon className="h-5 w-5 opacity-70"/>
                                    }
                                </button>
                            </div>
                            {errors.password && (
                                <p className="mt-1 text-primary-500">{errors.password.message}</p>
                            )}

                            <button
                                type="submit"
                                className={`rounded-md px-10 py-2 ${isSubmitDisabled
                                    ? "cursor-not-allowed text-gray-400"
                                    : "cursor-pointer bg-secondary-500 hover:bg-primary-500 hover:text-white"
                                }`}
                                disabled={isSubmitDisabled}
                            >
                                {isSubmitting || authStore.isLoading ? "Loading..." : "Become a Member"}
                            </button>
                        </form>
                    </motion.div>
                </motion.div>
            )}
        </AnimatePresence>,
        document.body
    );
});

export default RegisterModal;
