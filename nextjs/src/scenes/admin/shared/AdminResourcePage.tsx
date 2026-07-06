'use client'

import { observer } from "mobx-react-lite";
import { useRouter, useSearchParams } from "next/navigation";
import { ReactNode, useEffect, useState } from "react";
import Section, { emptyStateClassName, errorStateClassName, previewCardClassName, secondaryActionClassName } from "@/shared/Section";
import LoadingState from "@/shared/ui/LoadingState";
import PaginationControls from "@/shared/ui/PaginationControls";
import ConfirmDialog from "@/shared/ui/ConfirmDialog";
import AdminListToolbar, { AdminFilterField } from "@/scenes/admin/shared/AdminListToolbar";

type Pagination = {
    page: number;
    limit: number;
    total: number;
    pages: number;
};

export type AdminResourceStoreLike<TItem, TParams> = {
    items: TItem[];
    pagination: Pagination | null;
    isLoading: boolean;
    isRefreshing: boolean;
    error: string | null;
    mutationError: string | null;
    init: (params: TParams) => Promise<void>;
    isActionRunning: (id: number, action: string) => boolean;
};

type AdminResourcePageProps<TItem extends { id: number }, TParams> = {
    title: string;
    description: string;
    store: AdminResourceStoreLike<TItem, TParams>;
    parseParams: (searchParams: { get: (name: string) => string | null }) => TParams;
    filters: AdminFilterField[];
    sortOptions: { label: string; value: string }[];
    defaultSort: string;
    renderCreate?: ReactNode;
    renderItem: (item: TItem) => ReactNode;
    getActions?: (item: TItem) => { key: string; label: string; description: string; tone?: "default" | "danger"; run: () => Promise<unknown> }[];
};

type PendingAction = {
    key: string;
    label: string;
    description: string;
    tone: "default" | "danger";
    run: () => Promise<unknown>;
};

const AdminResourcePage = observer(<TItem extends { id: number }, TParams extends object>({
    title,
    description,
    store,
    parseParams,
    filters,
    sortOptions,
    defaultSort,
    renderCreate,
    renderItem,
    getActions,
}: AdminResourcePageProps<TItem, TParams>) => {
    const router = useRouter();
    const searchParams = useSearchParams();
    const [pendingAction, setPendingAction] = useState<PendingAction | null>(null);
    const paramsKey = searchParams.toString();

    useEffect(() => {
        void store.init(parseParams(searchParams));
    }, [parseParams, paramsKey, searchParams, store]);

    const pagination = store.pagination;

    return (
        <>
            <Section title={title} description={description} action={renderCreate}>
                <AdminListToolbar fields={filters} sortOptions={sortOptions} defaultSort={defaultSort} />
                {store.error ? (
                    <div className={errorStateClassName} role="alert">
                        <p>{store.error}</p>
                        <button
                            type="button"
                            className={`${secondaryActionClassName} mt-3`}
                            onClick={() => void store.init(parseParams(searchParams))}
                        >
                            Retry
                        </button>
                    </div>
                ) : null}
                {store.mutationError ? <p className={errorStateClassName} role="alert">{store.mutationError}</p> : null}
                {store.isLoading ? <LoadingState title="Loading records..." /> : null}
                {!store.isLoading && !store.error && store.items.length === 0 ? (
                    <p className={emptyStateClassName}>No records match the current filters.</p>
                ) : null}
                {pagination ? (
                    <p className="mb-4 text-sm text-gray-600" role="status" aria-live="polite">
                        {pagination.total} records{store.isRefreshing ? " · refreshing" : ""}
                    </p>
                ) : null}
                <div className={`grid gap-4 ${store.isRefreshing ? "opacity-70" : ""}`}>
                    {store.items.map((item) => {
                        const actions = getActions?.(item) ?? [];

                        return (
                            <article key={item.id} className={previewCardClassName}>
                                {renderItem(item)}
                                {actions.length > 0 ? (
                                    <div className="mt-4 flex flex-wrap gap-2 border-t border-gray-50 pt-4">
                                        {actions.map((action) => (
                                            <button
                                                key={action.key}
                                                type="button"
                                                className={secondaryActionClassName}
                                                disabled={store.isActionRunning(item.id, action.key)}
                                                onClick={() => setPendingAction({ ...action, tone: action.tone ?? "default" })}
                                            >
                                                {store.isActionRunning(item.id, action.key) ? "Working..." : action.label}
                                            </button>
                                        ))}
                                    </div>
                                ) : null}
                            </article>
                        );
                    })}
                </div>
                {pagination ? (
                    <div className="mt-6">
                        <PaginationControls
                            currentPage={pagination.page}
                            totalPages={pagination.pages}
                            disabled={store.isRefreshing}
                            onPageChange={(page) => {
                                const next = new URLSearchParams(searchParams.toString());
                                next.set("page", page.toString());
                                router.push(`?${next.toString()}`);
                            }}
                        />
                    </div>
                ) : null}
            </Section>
            <ConfirmDialog
                open={pendingAction !== null}
                title={pendingAction?.label ?? "Confirm action"}
                description={pendingAction?.description ?? "Confirm this action."}
                tone={pendingAction?.tone}
                confirmLabel="Confirm"
                onCancel={() => setPendingAction(null)}
                onConfirm={() => {
                    const action = pendingAction;
                    if (!action) {
                        return;
                    }
                    void action.run().finally(() => setPendingAction(null));
                }}
            />
        </>
    );
});

export default AdminResourcePage;
