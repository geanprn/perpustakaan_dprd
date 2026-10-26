export interface User {
    id: number;
    name: string;
    username: string;
}

export interface FlashMessages {
    success?: string | null;
    error?: string | null;
}

export interface Category {
    id: number;
    name: string;
    books_count?: number;
}

export interface Book {
    id: number;
    category_id: number;
    title: string;
    author: string;
    publisher: string | null;
    publication_year: number | null;
    shelf_location: string | null;
    total_copies: number;
    available_copies: number;
    category?: Category | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
}

export interface SharedProps {
    appName: string;
    auth: {
        user: User | null;
    };
    flash: FlashMessages;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & SharedProps;
