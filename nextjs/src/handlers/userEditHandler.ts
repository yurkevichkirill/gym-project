import {notify} from "@/lib/notify";
import {editMe} from "@/api/user.api";

export const handleEditUser = async (phone: string) => {
    const toastId = notify.loading("Editing user...");

    try {
        const res = await editMe({
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