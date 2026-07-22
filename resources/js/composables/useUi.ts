import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    formatDateValue,
    formatNumberValue,
    formatRelativeValue,
    resolveIntlLocale,
} from '@/lib/formatters';
import type { FormattingPreferences } from '@/lib/formatters';

type TranslationReplacements = Record<string, number | string>;

export function useUi() {
    const page = usePage();
    const preferences = computed<FormattingPreferences>(
        () => page.props.preferences ?? {},
    );
    const locale = computed(() =>
        resolveIntlLocale(preferences.value.language),
    );
    const timezone = computed(() => preferences.value.timezone ?? 'UTC');

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
        return formatDateValue(value, options, preferences.value);
    }

    function formatNumber(
        value: number,
        options: Intl.NumberFormatOptions = {},
    ): string {
        return formatNumberValue(value, options, preferences.value);
    }

    function formatRelative(
        value: Date | number | string,
        base: Date | number | string = new Date(),
    ): string {
        return formatRelativeValue(value, base, preferences.value);
    }

    return {
        formatDate,
        formatNumber,
        formatRelative,
        locale,
        t: translate,
        timezone,
    };
}
