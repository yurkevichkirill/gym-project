import { ApiClientError } from "@/lib/apiClient";

export const getMembershipMutationErrorMessage = (
    error: unknown,
    fallback: string,
): string => {
    if (!(error instanceof ApiClientError)) {
        return error instanceof Error && error.message.length > 0
            ? error.message
            : fallback;
    }

    if (error.status === 409) {
        return error.payload.message
            || "The membership state changed on the server. Refresh the data and try again.";
    }

    if (error.status === 422) {
        const violations = error.payload.violations
            ?.map((violation) => violation.message ?? violation.title)
            .filter((message): message is string => Boolean(message));

        if (violations && violations.length > 0) {
            return violations.join(" ");
        }

        return error.payload.message || "The membership data is invalid.";
    }

    return error.payload.message || fallback;
};
