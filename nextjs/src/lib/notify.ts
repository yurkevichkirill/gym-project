import { toast } from "sonner";

export const notify = {
    success: (title: string, description?: string, id?: string | number) =>
        toast.success(title, { description, id }),

    error: (title: string, description?: string, id?: string | number) =>
        toast.error(title, { description, id }),

    info: (title: string, description?: string) =>
        toast(title, { description }),

    loading: (title: string, description?: string) =>
        toast.loading(title, { description }),
};