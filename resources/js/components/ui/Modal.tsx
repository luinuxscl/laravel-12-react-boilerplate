import React from 'react';

type ModalProps = {
  open: boolean;
  onClose: () => void;
  title?: string;
  children: React.ReactNode;
  footer?: React.ReactNode;
};

export function Modal({ open, onClose, title, children, footer }: ModalProps) {
  if (!open) return null;
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black/40" onClick={onClose} />
      <div className="relative z-10 w-full max-w-lg rounded-md bg-background shadow-lg">
        {title && (
          <div className="border-b px-4 py-3 text-base font-semibold">{title}</div>
        )}
        <div className="px-4 py-3">{children}</div>
        {footer && <div className="border-t px-4 py-3">{footer}</div>}
      </div>
    </div>
  );
}

export default Modal;
