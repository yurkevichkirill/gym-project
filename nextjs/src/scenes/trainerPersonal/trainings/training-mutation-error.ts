import { ApiClientError } from "@/lib/apiClient";

export const getTrainerTrainingMutationErrorMessage = (
    error: unknown,
    fallback: string,
): string => {
    if (!(error instanceof ApiClientError)) {
        return error instanceof Error && error.message.length > 0
            ? error.message
            : fallback;
    }

    if (error.status === 403) {
        return error.payload.message
            || "You cannot change a training that does not belong to your trainer account.";
    }

    if (error.status === 404) {
        return error.payload.message || "The training no longer exists.";
    }

    if (error.status === 409) {
        return error.payload.message
            || "The training status or schedule changed on the server. Review the refreshed data and try again.";
    }

    if (error.status === 422) {
        const violations = error.payload.violations
            ?.map((violation) => violation.message ?? violation.title)
            .filter((message): message is string => Boolean(message));

        if (violations && violations.length > 0) {
            return violations.join(" ");
        }

        return error.payload.message || "The training update data is invalid.";
    }

    if (error.status === 400) {
        return error.payload.message
            || "The action is not available for this training date or status.";
    }

    return error.payload.message || fallback;
};
