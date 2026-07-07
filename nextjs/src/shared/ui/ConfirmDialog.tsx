'use client'

import { primaryActionClassName, secondaryActionClassName } from "@/shared/Section";
import { useEffect, useId, useRef } from "react";

type ConfirmDialogProps = {
    open: boolean;
    title: string;
    description: string;
    confirmLabel?: string;
    cancelLabel?: string;
    isConfirming?: boolean;
    tone?: "default" | "danger";
    onConfirm: () => void;
    onCancel: () => void;
};

const ConfirmDialog = ({
    open,
    title,
    description,
    confirmLabel = "Confirm",
    cancelLabel = "Cancel",
    isConfirming = false,
    tone = "default",
    onConfirm,
    onCancel,
}: ConfirmDialogProps) => {
    const dialogRef = useRef<HTMLDialogElement>(null);
    const titleId = useId();
    const descriptionId = useId();

    useEffect(() => {
        const dialog = dialogRef.current;
        if (!dialog) {
            return;
        }

        if (open && !dialog.open) {
            dialog.showModal();
        } else if (!open && dialog.open) {
            dialog.close();
        }

        return () => {
            if (dialog.open) {
                dialog.close();
            }
        };
    }, [open]);

    const confirmClassName = tone === "danger"
        ? `${primaryActionClassName} bg-primary-500 text-white hover:bg-primary-300 hover:text-gray-900`
        : primaryActionClassName;

    return (
        <dialog
            ref={dialogRef}
            className="fixed top-6 left-1/2 m-0 w-[min(92vw,32rem)] -translate-x-1/2 rounded-3xl border border-gray-100 bg-white/95 p-0 text-gray-500 shadow-xl backdrop:bg-gray-900/40 sm:top-8"
            aria-labelledby={titleId}
            aria-describedby={descriptionId}
            onCancel={(event) => {
                event.preventDefault();
                if (!isConfirming) {
                    onCancel();
                }
            }}
            onClick={(event) => {
                if (event.target === event.currentTarget && !isConfirming) {
                    onCancel();
                }
            }}
        >
            <div className="p-5 sm:p-6">
                <div className="flex items-start gap-3 border-b border-gray-50 pb-4">
                    <span className="mt-1 h-7 w-1 shrink-0 rounded-full bg-secondary-500" aria-hidden="true" />
                    <div className="min-w-0">
                        <h2 id={titleId} className="text-xl font-bold text-gray-500">
                            {title}
                        </h2>
                        <p id={descriptionId} className="mt-2 text-sm leading-6 text-gray-600">
                            {description}
                        </p>
                    </div>
                </div>

                <div className="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        className={secondaryActionClassName}
                        disabled={isConfirming}
                        onClick={onCancel}
                    >
                        {cancelLabel}
                    </button>
                    <button
                        type="button"
                        className={confirmClassName}
                        disabled={isConfirming}
                        onClick={onConfirm}
                    >
                        {isConfirming ? "Please wait..." : confirmLabel}
                    </button>
                </div>
            </div>
        </dialog>
    );
};

export default ConfirmDialog;
