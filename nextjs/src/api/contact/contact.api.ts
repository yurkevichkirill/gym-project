import { apiPost } from "@/lib/apiClient";
import type { ContactRequest } from "@/types/contact/contact-request.type";

export const submitContactRequest = (
    request: ContactRequest,
): Promise<null> => {
    return apiPost<null, ContactRequest>("/contact/", request, {
        skipAuthRefresh: true,
    });
};
