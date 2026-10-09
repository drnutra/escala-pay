<script setup>
import { cva } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'primary', 'destructive', 'danger', 'outline', 'secondary', 'ghost', 'link'].includes(v),
    },
    size: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'sm', 'lg', 'icon'].includes(v),
    },
    as: { type: String, default: 'button' },
    class: { type: [String, Object, Array], default: '' },
});

const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl text-[13px] font-medium transition-[background-color,border-color,color,transform,box-shadow] duration-150 active:scale-[0.97] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--ep-accent)] focus-visible:ring-offset-0 disabled:pointer-events-none disabled:opacity-50',
    {
        variants: {
            variant: {
                default: 'ep-btn !h-auto',
                primary: 'ep-btn !h-auto',
                destructive: 'ep-btn-danger !h-auto',
                danger: 'ep-btn-danger !h-auto',
                outline: 'ep-btn-secondary !h-auto',
                secondary: 'ep-btn-secondary !h-auto',
                ghost: 'ep-btn-ghost !h-auto',
                link: 'text-[var(--ep-accent)] underline-offset-4 hover:underline',
            },
            size: {
                default: 'h-9 px-4',
                sm: 'h-8 rounded-[10px] px-3 text-xs',
                lg: 'h-11 rounded-xl px-6 text-sm',
                icon: 'h-9 w-9',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    }
);
</script>

<template>
    <component
        :is="as"
        :class="cn(buttonVariants({ variant, size }), props.class)"
    >
        <slot />
    </component>
</template>
