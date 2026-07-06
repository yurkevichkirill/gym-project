import { ApiCollectionResponse } from "@/types/api-collection-response";

export type AdminPagination<T> = ApiCollectionResponse<T[]>["meta"]["pagination"];

export type SearchParamsReader = {
    get: (name: string) => string | null;
};

export type AdminListQuery = Record<string, string | number | boolean | undefined | null>;

export type AdminActionState = {
    key: string;
    label: string;
};

