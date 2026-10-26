import { cn } from '@/lib/utils';
import { cva, type VariantProps } from 'class-variance-authority';
import { HTMLAttributes } from 'react';

const badgeVariants = cva(
    'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
    {
        variants: {
            variant: {
                default: 'border-transparent bg-gray-900 text-white',
                secondary: 'border-gray-200 bg-gray-100 text-gray-700',
                success: 'border-green-200 bg-green-50 text-green-700',
                destructive: 'border-red-200 bg-red-50 text-red-700',
                outline: 'border-gray-300 text-gray-700',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    },
);

export interface BadgeProps
    extends HTMLAttributes<HTMLSpanElement>,
        VariantProps<typeof badgeVariants> {}

export default function Badge({
    className,
    variant,
    ...props
}: BadgeProps) {
    return (
        <span className={cn(badgeVariants({ variant }), className)} {...props} />
    );
}

export { badgeVariants };
