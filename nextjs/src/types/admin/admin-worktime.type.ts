export interface AdminWorktimeCreateRequest {
    startTime: string;
    endTime: string;
    date: string;
}

export interface AdminWorktimeUpdateRequest {
    startTime?: string;
    endTime?: string;
}

