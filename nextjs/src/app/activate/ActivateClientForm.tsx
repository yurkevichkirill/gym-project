'use client';

import {useMemo, useState} from "react";
import type {FormEvent} from "react";
import {useSearchParams} from "next/navigation";
import {ApiClientError, apiPost} from "@/lib/apiClient";
import {ApiItemResponse} from "@/types/api-item-response.type";
import ClientType from "@/types/client/client.type";

const TOKEN_PATTERN = /^[a-f0-9]{64}$/i;

const ActivateClientForm = () => {
    const searchParams = useSearchParams();
    const activationToken = useMemo(
        () => searchParams.get('token')?.trim() ?? '',
        [searchParams],
    );
    const [password, setPassword] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isActivated, setIsActivated] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const hasValidToken = TOKEN_PATTERN.test(activationToken);

    const submit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!hasValidToken || isSubmitting) {
            return;
        }

        setIsSubmitting(true);
        setError(null);

        try {
            await apiPost<ApiItemResponse<ClientType>>('/clients/activate/', {
                activationToken,
                password,
            });
            setIsActivated(true);
            setPassword('');
        } catch (caughtError) {
            if (caughtError instanceof ApiClientError) {
                const violationMessage = caughtError.payload.violations?.[0]?.message;
                setError(violationMessage ?? caughtError.message);
            } else {
                setError('Account activation failed. Please try again.');
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    if (!hasValidToken) {
        return (
            <section className="w-full rounded-2xl bg-white p-8 shadow-lg">
                <h1 className="font-montserrat text-3xl font-bold text-gray-500">Invalid activation link</h1>
                <p className="mt-4">
                    The activation token is missing or malformed. Open the complete link from your activation email.
                </p>
            </section>
        );
    }

    if (isActivated) {
        return (
            <section className="w-full rounded-2xl bg-white p-8 shadow-lg">
                <h1 className="font-montserrat text-3xl font-bold text-gray-500">Account activated</h1>
                <p className="mt-4">Your password has been saved. You can now sign in.</p>
            </section>
        );
    }

    return (
        <section className="w-full rounded-2xl bg-white p-8 shadow-lg">
            <h1 className="font-montserrat text-3xl font-bold text-gray-500">Activate your account</h1>
            <p className="mt-3">Create a password to finish setting up your EvoGym account.</p>

            <form className="mt-8 space-y-5" onSubmit={submit}>
                <div>
                    <label className="mb-2 block font-semibold" htmlFor="activation-password">
                        Password
                    </label>
                    <input
                        id="activation-password"
                        type="password"
                        autoComplete="new-password"
                        required
                        minLength={8}
                        value={password}
                        onChange={(event) => setPassword(event.target.value)}
                        className="w-full rounded-lg border border-gray-100 bg-gray-20 px-4 py-3 outline-none focus:border-primary-500"
                    />
                </div>

                {error !== null && (
                    <p role="alert" className="rounded-lg bg-primary-100 px-4 py-3 text-sm">
                        {error}
                    </p>
                )}

                <button
                    type="submit"
                    disabled={isSubmitting}
                    className="w-full rounded-lg bg-primary-500 px-5 py-3 font-bold text-white transition-opacity disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {isSubmitting ? 'Activating...' : 'Activate account'}
                </button>
            </form>
        </section>
    );
};

export default ActivateClientForm;
