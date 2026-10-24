export interface User {
    id: number;
    name: string;
    username: string;
}

export interface FlashMessages {
    success?: string | null;
    error?: string | null;
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