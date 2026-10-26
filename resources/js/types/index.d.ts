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

/**
 * Data buku yang aman ditampilkan ke pengunjung katalog.
 *
 * `available_copies` bernilai null bila config library.show_exact_stock
 * false — pengunjung hanya melihat status lewat `is_available`.
 */
export interface CatalogBook {
    id: number;
    title: string;
    author: string;
    category: string | null;
    shelf_location: string | null;
    total_copies: number;
    available_copies: number | null;
    is_available: boolean;
}

export interface CatalogBookDetail extends CatalogBook {
    publisher: string | null;
    publication_year: number | null;
}

export interface CatalogCategory {
    id: number;
    name: string;
    books_count: number;
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
