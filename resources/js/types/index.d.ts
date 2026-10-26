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

export interface Borrower {
    id: number;
    name: string;
    identity_number: string;
    institution: string | null;
    phone: string | null;
    address: string | null;
    loans_count?: number;
}

export interface BorrowerDetail {
    id: number;
    name: string;
    identity_number: string;
    institution: string | null;
    phone: string | null;
    address: string | null;
    created_at: string | null;
}

export interface BorrowerLoan {
    id: number;
    loan_number: string;
    loan_date: string | null;
    due_date: string | null;
    status: 'berjalan' | 'selesai';
    petugas: string | null;
    jumlah_judul: number;
    jumlah_eksemplar: number;
}

export interface BorrowerOption {
    id: number;
    name: string;
    identity_number: string;
}

export interface LoanFormBook {
    id: number;
    title: string;
    author: string;
    available_copies: number;
}

export interface LoanBorrowerSummary {
    id: number;
    name: string | null;
    identity_number: string | null;
}

export interface LoanListItem {
    id: number;
    loan_number: string;
    borrower: LoanBorrowerSummary;
    loan_date: string | null;
    due_date: string | null;
    status: 'berjalan' | 'selesai';
    is_overdue: boolean;
    jumlah_judul: number;
    jumlah_eksemplar: number;
}

export interface LoanReturnEntry {
    id: number;
    return_date: string | null;
    quantity: number;
    petugas: string | null;
}

export interface LoanDetailItem {
    id: number;
    title: string | null;
    author: string | null;
    quantity: number;
    returned_quantity: number;
    remaining_quantity: number;
    status: 'dipinjam' | 'selesai';
    returns: LoanReturnEntry[];
}

export interface LoanDetail {
    id: number;
    loan_number: string;
    loan_date: string | null;
    due_date: string | null;
    status: 'berjalan' | 'selesai';
    is_overdue: boolean;
    notes: string | null;
    petugas: string | null;
    borrower: {
        id: number;
        name: string | null;
        identity_number: string | null;
        institution: string | null;
        phone: string | null;
    };
}

export type LoanStatusFilter = '' | 'berjalan' | 'selesai' | 'terlambat';

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
