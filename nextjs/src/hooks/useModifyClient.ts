import { useState } from "react";
import { notify } from "@/lib/notify";
import {useStore} from "@/store/StoreProvider";
import {useRouter} from "next/navigation";

export const useModifyClient = (initialPhone: string) => {
    const [newPhone, setNewPhone] = useState(initialPhone);
    const [onEdit, setOnEdit] = useState(false);
    const [loading, setLoading] = useState(false);
    const router = useRouter();

    const { clientStore } = useStore();

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
            await clientStore.update({ phone: newPhone });

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

    const handleDelete = async () => {
        if (!confirm("Delete your profile?")) return;

        const toastId = notify.loading("Deleting account...");

        try {
            await clientStore.delete();

            router.push("/");

            notify.success(
                "Account was deleted",
                "Your profile was removed",
                toastId
            );

        } catch (error: any) {
            notify.error(
                "Deleting failed",
                error?.message || "Something went wrong",
                toastId,
            );
        }
    }

    return {
        newPhone,
        setNewPhone,
        onEdit,
        setOnEdit,
        loading,
        handleEdit,
        handleDelete,
    };
};