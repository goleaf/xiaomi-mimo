import { toast } from 'vue-sonner';
import { useUi } from '@/composables/useUi';

export function useToast() {
    const { t } = useUi();
    function success(message: string) {
        toast.success(message);
    }

    function error(message: string) {
        toast.error(message);
    }

    function info(message: string) {
        toast.info(message);
    }

    function warning(message: string) {
        toast.warning(message);
    }

    function withUndo(message: string, undoFn: () => void) {
        toast(message, {
            action: {
                label: t('common.toast.undo'),
                onClick: undoFn,
            },
        });
    }

    return { success, error, info, warning, withUndo };
}
