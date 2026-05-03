export type ApiCollectionResponse<T> = {
    data: T
    meta: {
        pagination: {
            page: number
            limit: number
            total: number
            pages: number
        }
        sort?: Record<string, string>
    }
}