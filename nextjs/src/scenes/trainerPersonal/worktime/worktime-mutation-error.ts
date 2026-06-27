import { ApiClientError } from "@/lib/apiClient";

export const getTrainerWorktimeMutationErrorMessage = (
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
            || "The worktime conflicts with existing schedule or training history.";
    }

    if (error.status === 422) {
        const violations = error.payload.violations
            ?.map((violation) => violation.message ?? violation.title)
            .filter((message): message is string => Boolean(message));

        if (violations && violations.length > 0) {
            return violations.join(" ");
        }

        return error.payload.message || "The worktime data is invalid.";
    }

    if (error.status === 400) {
        return error.payload.message || "The worktime interval is invalid.";
    }

    if (error.status === 403) {
        return error.payload.message || "You cannot change this worktime.";
    }

    if (error.status === 404) {
        return error.payload.message || "The worktime no longer exists.";
    }

    return error.payload.message || fallback;
};
