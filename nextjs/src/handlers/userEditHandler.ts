import {notify} from "@/lib/notify";
import {update} from "@/api/client/client.api";

export const handleEditUser = async (phone: string) => {
    const toastId = notify.loading("Editing user...");

    try {
        const res = await update({
            phone,
        });

        notify.success(
            "Client edited",
            `New phone: ${res.phone}`,
            toastId
        );

    } catch (error: any) {
        notify.error(
            "Editing failed",
            error?.message || "Something went wrong",
            toastId,
        );
    }
}