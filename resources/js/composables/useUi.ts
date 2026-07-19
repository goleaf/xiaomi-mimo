import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const localeMap: Record<string, string> = {
    en: 'en-US',
    lt: 'lt-LT',
    ru: 'ru-RU',
};

type TranslationReplacements = Record<string, number | string>;

function normalizeDate(value: Date | number | string): Date {
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return new Date(`${value}T12:00:00`);
    }

    return value instanceof Date ? value : new Date(value);
}

export function useUi() {
    const page = usePage();
    const locale = computed(
        () => localeMap[page.props.preferences?.language ?? 'en'] ?? 'en-US',
    );
    const timezone = computed(
        () =>
            page.props.preferences?.timezone ??
            Intl.DateTimeFormat().resolvedOptions().timeZone,
    );

    function translate(
        key: string,
        replacements: TranslationReplacements = {},
    ): string {
        const value = key.split('.').reduce<unknown>((copy, segment) => {
            if (
                typeof copy !== 'object' ||
                copy === null ||
                !(segment in copy)
            ) {
                return undefined;
            }

            return (copy as Record<string, unknown>)[segment];
        }, page.props.ui);

        if (typeof value !== 'string') {
            return key;
        }

        return value.replace(/:([A-Za-z_]+)/g, (match, name: string) =>
            name in replacements ? String(replacements[name]) : match,
        );
    }

    function formatDate(
        value: Date | number | string,
        options: Intl.DateTimeFormatOptions,
    ): string {
        const date = normalizeDate(value);

        if (Number.isNaN(date.getTime())) {
            return '';
        }

        try {
            return new Intl.DateTimeFormat(locale.value, {
                ...options,
                timeZone: timezone.value,
            }).format(date);
        } catch {
            return new Intl.DateTimeFormat('en-US', options).format(date);
        }
    }

    function formatNumber(
        value: number,
        options: Intl.NumberFormatOptions = {},
    ): string {
        try {
            return new Intl.NumberFormat(locale.value, options).format(value);
        } catch {
            return new Intl.NumberFormat('en-US', options).format(value);
        }
    }

    return {
        formatDate,
        formatNumber,
        locale,
        t: translate,
        timezone,
    };
}
