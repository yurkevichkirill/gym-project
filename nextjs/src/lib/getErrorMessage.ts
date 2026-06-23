export const getErrorMessage = (
    error: unknown,
    fallback = "Something went wrong",
): string => {
    return error instanceof Error && error.message.length > 0
        ? error.message
        : fallback;
};
