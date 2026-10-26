import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

/**
 * Paginasi Laravel — menerima daftar link dari paginator length-aware.
 */
export default function Pagination({
    links,
    className,
}: {
    links: PaginationLink[];
    className?: string;
}) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav
            className={cn(
                'flex flex-wrap items-center justify-center gap-1',
                className,
            )}
            aria-label="Navigasi halaman"
        >
            {links.map((link, index) => {
                const isPrevious = index === 0;
                const isNext = index === links.length - 1;

                const label = isPrevious ? (
                    <ChevronLeft className="h-4 w-4" />
                ) : isNext ? (
                    <ChevronRight className="h-4 w-4" />
                ) : (
                    <span
                        dangerouslySetInnerHTML={{ __html: link.label }}
                        aria-label={`Halaman ${link.label}`}
                    />
                );

                const button = (
                    <Button
                        variant={link.active ? 'default' : 'outline'}
                        size={isPrevious || isNext ? 'icon' : 'sm'}
                        disabled={link.url === null}
                        className={cn(
                            link.url === null && 'cursor-not-allowed opacity-50',
                        )}
                        aria-current={link.active ? 'page' : undefined}
                    >
                        {label}
                    </Button>
                );

                if (link.url === null) {
                    return (
                        <span key={`${link.label}-${index}`}>{button}</span>
                    );
                }

                return (
                    <Link
                        key={`${link.label}-${index}`}
                        href={link.url}
                        preserveScroll
                        className="inline-flex"
                    >
                        {button}
                    </Link>
                );
            })}
        </nav>
    );
}
