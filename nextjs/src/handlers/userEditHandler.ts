import {notify} from "@/lib/notify";
import {update} from "@/api/client/client.api";
import {getErrorMessage} from "@/lib/getErrorMessage";

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
    } catch (error: unknown) {
        notify.error(
            "Editing failed",
            getErrorMessage(error),
            toastId,
        );
    }
};
