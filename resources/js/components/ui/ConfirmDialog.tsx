import React from 'react';
import Modal from '@/components/ui/Modal';
import { useTranslation } from 'react-i18next';

export type ConfirmDialogProps = {
  open: boolean;
  title?: string;
  description?: string;
  confirmLabel?: string;
  cancelLabel?: string;
  onConfirm: () => void | Promise<void>;
  onClose: () => void;
  loading?: boolean;
};

export default function ConfirmDialog({
  open,
  title,
  description,
  confirmLabel,
  cancelLabel,
  onConfirm,
  onClose,
  loading = false,
}: ConfirmDialogProps) {
  const { t } = useTranslation();
  const finalTitle = title ?? t('common.confirm_title');
  const finalDesc = description ?? t('common.confirm_description');
  const finalConfirm = confirmLabel ?? t('actions.confirm');
  const finalCancel = cancelLabel ?? t('actions.cancel');
  return (
    <Modal
      open={open}
      onClose={onClose}
      title={finalTitle}
      footer={(
        <div className="flex items-center justify-end gap-2">
          <button className="rounded-md border px-3 py-1.5 text-sm" onClick={onClose} disabled={loading}>{finalCancel}</button>
          <button
            className="rounded-md border px-3 py-1.5 text-sm bg-destructive text-destructive-foreground disabled:opacity-50"
            onClick={onConfirm}
            disabled={loading}
          >
            {finalConfirm}
          </button>
        </div>
      )}
    >
      <div className="text-sm">{finalDesc}</div>
    </Modal>
  );
}
