import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    formatDateValue,
    formatNumberValue,
    resolveIntlLocale,
} from '@/lib/formatters';
import type { FormattingPreferences } from '@/lib/formatters';
import type { WorkspaceUiPageProps } from '@/types/workspace-ui';

export function useWorkspaceUi() {
    const page = usePage<WorkspaceUiPageProps>();
    const copy = computed(() => page.props.workspaceUi);
    const preferences = computed<FormattingPreferences>(
        () => page.props.preferences ?? {},
    );
    const locale = computed(() =>
        resolveIntlLocale(preferences.value.language),
    );
    const timezone = computed(() => preferences.value.timezone ?? 'UTC');

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

    return {
        copy,
        formatDate,
        formatNumber,
        locale,
        timezone,
    };
}
