'use client';

import {useForm} from "react-hook-form";
import {useStore} from "@/store/StoreProvider";
import {createPortal} from "react-dom";
import {AnimatePresence, motion} from "framer-motion";
import {EyeIcon, EyeSlashIcon} from "@heroicons/react/24/solid";
import {useState} from "react";
import {observer} from "mobx-react-lite";
import {notify} from "@/lib/notify";
import {getErrorMessage} from "@/lib/getErrorMessage";
import {useRouter} from "next/navigation";

interface FormData {
    email: string;
    password: string;
}

const LoginModal = observer(({
    isOpen,
    onClose,
}: {
    isOpen: boolean;
    onClose: () => void;
}) => {
    const {authStore} = useStore();
    const router = useRouter();

    const {
        register,
        handleSubmit,
        formState: {errors, isSubmitting},
    } = useForm<FormData>({
        mode: "onChange",
    });

    const onSubmit = async (data: FormData) => {
        const toastId = notify.loading("Logging in...");

        try {
            const user = await authStore.login(data);
            onClose();
            router.replace("/me");

            notify.success(
                "Logged in successfully",
                `User ${user.email} signed in`,
                toastId,
            );
        } catch (error: unknown) {
            notify.error(
                "Logging failed",
                getErrorMessage(error),
                toastId,
            );
        }
    };

    const [passVisible, setPassVisible] = useState(false);
    const isSubmitDisabled = isSubmitting
        || authStore.isLoading
        || Object.keys(errors).length > 0;

    return createPortal(
        <AnimatePresence>
            {isOpen && (
            <motion.div
                className="fixed inset-0 bg-black/50 flex justify-center items-center z-50"
                onClick={onClose}
                initial={{opacity: 0}}
                animate={{opacity: 1}}
                exit={{opacity: 0}}
            >
                <motion.div
                    className="bg-white p-6 rounded-xl w-[300px] relative"
                    onClick={(event) => event.stopPropagation()}
                    initial={{opacity: 0, y: 50}}
                    animate={{opacity: 1, y: 0}}
                    transition={{delay: 0.2, duration: 0.5}}
                >
                    <button
                        type="button"
                        onClick={onClose}
                        className="absolute top-2 right-2 text-gray-500 cursor-pointer"
                    >
                        ✕
                    </button>
                    <h2 className="text-xl font-bold mb-1">Sign In</h2>
                    <div className="h-[1px] bg-gray-100 mb-2"></div>
                    <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-3">
                        <div
                            className={`border rounded
                                ${errors.email ? "border-primary-500" : "border-secondary-500"}
                            `}
                        >
                            <input
                                type="text"
                                placeholder="Email"
                                {...register("email", {
                                    required: true,
                                    pattern: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                                })}
                                className="m-2 outline-none"
                            />
                        </div>
                        {errors.email && (
                            <p className="mt-1 text-primary-500">
                                {errors.email.type === "required" && "Email is required."}
                                {errors.email.type === "pattern" && "Invalid email address."}
                            </p>
                        )}
                        <div
                            className={`border rounded
                                ${errors.password ? "border-primary-500" : "border-secondary-500"}
                            `}
                        >
                            <input
                                type={passVisible ? "text" : "password"}
                                placeholder="Password"
                                {...register("password", {
                                    required: true,
                                    maxLength: 100,
                                })}
                                className="m-2 outline-none"
                            />
                            <button
                                onClick={() => setPassVisible(!passVisible)}
                                className="align-middle"
                                type="button"
                            >
                                {passVisible
                                    ? <EyeIcon className="h-5 w-5 opacity-70"/>
                                    : <EyeSlashIcon className="h-5 w-5 opacity-70"/>
                                }
                            </button>
                        </div>
                        {errors.password && (
                            <p className="mt-1 text-primary-500">
                                {errors.password.type === "required" && "Password is required."}
                                {errors.password.type === "maxLength" && "Max length is 100 char."}
                            </p>
                        )}
                        <button
                            type="submit"
                            className={`rounded-md px-10 py-2 ${isSubmitDisabled
                                ? "cursor-not-allowed text-gray-400"
                                : "cursor-pointer bg-secondary-500 hover:bg-primary-500 hover:text-white"
                            }`}
                            disabled={isSubmitDisabled}
                        >
                            {isSubmitting || authStore.isLoading ? "Loading..." : "Login"}
                        </button>
                    </form>
                </motion.div>
            </motion.div>
            )}
        </AnimatePresence>,
        document.body,
    );
});

export default LoginModal;
