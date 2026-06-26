'use client'

import {useEffect, useId, useRef} from "react";

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
        ? "bg-red-600 text-white hover:bg-red-700"
        : "bg-secondary-500 hover:bg-primary-500 hover:text-white";

    return (
        <dialog
            ref={dialogRef}
            className="w-[min(92vw,32rem)] rounded-2xl bg-white p-0 shadow-xl backdrop:bg-black/50"
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
            <div className="p-6 sm:p-8">
                <h2 id={titleId} className="text-2xl font-bold">
                    {title}
                </h2>
                <p id={descriptionId} className="mt-3 text-gray-600">
                    {description}
                </p>
                <div className="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        className="rounded-md border border-gray-300 bg-white px-5 py-2 font-semibold transition hover:border-primary-500 disabled:cursor-not-allowed disabled:opacity-50"
                        disabled={isConfirming}
                        onClick={onCancel}
                    >
                        {cancelLabel}
                    </button>
                    <button
                        type="button"
                        className={`rounded-md px-5 py-2 font-semibold transition disabled:cursor-not-allowed disabled:opacity-50 ${confirmClassName}`}
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
