import { useState } from "react";
import { editMe } from "@/api/user.api";
import { notify } from "@/lib/notify";

export const useEditUser = (initialPhone: string) => {
    const [newPhone, setNewPhone] = useState(initialPhone);
    const [onEdit, setOnEdit] = useState(false);
    const [loading, setLoading] = useState(false);

    const handleEdit = async () => {
        if (!onEdit) {
            setOnEdit(true);
            return;
        }

        if (newPhone === initialPhone) {
            setOnEdit(false);
            return;
        }

        const toastId = notify.loading("Updating profile...");
        setLoading(true);

        try {
            await editMe({ phone: newPhone });

            notify.success("Profile updated", "Phone number changed", toastId);
            setOnEdit(false);
        } catch (error: any) {
            notify.error(
                "Editing failed",
                error?.message || "Something went wrong",
                toastId,
            );
        } finally {
            setLoading(false);
        }
    };

    return {
        newPhone,
        setNewPhone,
        onEdit,
        setOnEdit,
        loading,
        handleEdit,
    };
};