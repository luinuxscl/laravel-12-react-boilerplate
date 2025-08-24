import React from 'react';
import Modal from '@/components/ui/Modal';

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
  title = 'Are you sure?',
  description = 'This action cannot be undone.',
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  onConfirm,
  onClose,
  loading = false,
}: ConfirmDialogProps) {
  return (
    <Modal
      open={open}
      onClose={onClose}
      title={title}
      footer={(
        <div className="flex items-center justify-end gap-2">
          <button className="rounded-md border px-3 py-1.5 text-sm" onClick={onClose} disabled={loading}>{cancelLabel}</button>
          <button
            className="rounded-md border px-3 py-1.5 text-sm bg-destructive text-destructive-foreground disabled:opacity-50"
            onClick={onConfirm}
            disabled={loading}
          >
            {confirmLabel}
          </button>
        </div>
      )}
    >
      <div className="text-sm">{description}</div>
    </Modal>
  );
}
