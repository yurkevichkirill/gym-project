export interface AdminClient {
    id: number;
    age: number;
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
    createdAt: string;
    deletedAt: string;
    updatedAt: string;
    balance: number;
    type: "client";
    blockedAt: string;
}

export interface AdminClientsGetQueryParams {
    minAge?: number;
    maxAge?: number;
    minBalance?: number;
    maxBalance?: number;
    isDeleted?: boolean;
    sort?: string;
    page?: number;
    limit?: number;
}

export interface AdminClientCreateRequest {
    age: number;
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
    password: string;
}

export interface AdminClientUpdateRequest {
    age?: number;
    firstName?: string;
    lastName?: string;
    email?: string;
    phone?: string;
    password?: string;
}

export interface AdminClientImportItem {
    age: number;
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
}

export interface AdminClientImportRequest {
    clients: AdminClientImportItem[];
}

export interface AdminClientImportResponse {
    status: string;
    count: number;
    jobId: number;
}
