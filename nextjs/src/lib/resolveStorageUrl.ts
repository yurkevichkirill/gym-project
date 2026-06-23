export const resolveStorageUrl = (
    path: string | null,
    fallback: string,
): string => {
    if (!path) {
        return fallback;
    }

    if (path.startsWith("http://") || path.startsWith("https://")) {
        return path;
    }

    const baseUrl = process.env.NEXT_PUBLIC_STORAGE_URL
        || "http://localhost:9005/evogym-bucket";

    return `${baseUrl.replace(/\/$/, "")}/${path.replace(/^\//, "")}`;
};
