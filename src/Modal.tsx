import React from 'react';
import './Modal.css';

interface ModalProps {
  isOpen: boolean;
  title: string;
  message: string;
  onConfirm: () => void;
  onCancel: () => void;
}

const Modal: React.FC<ModalProps> = ({ isOpen, title, message, onConfirm, onCancel }) => {
  if (!isOpen) return null;

  // Prevent clicks inside the modal from closing it
  const handleModalContentClick = (e: React.MouseEvent) => {
    e.stopPropagation();
  };

  return (
    <div className="modal-overlay" onClick={onCancel}>
      <div className="modal-content" onClick={handleModalContentClick}>
        <h3 className="modal-title">{title}</h3>
        <p className="modal-message">{message}</p>
        <div className="modal-actions">
          <button className="modal-button modal-cancel" onClick={onCancel}>
            Cancel
          </button>
          <button className="modal-button modal-confirm" onClick={onConfirm}>
            Yes, I'm sure
          </button>
        </div>
      </div>
    </div>
  );
};

export default Modal;
