import React from 'react';
import '../../../index.css'; // Styles will be applied from index.css

const Button = ({ text, onClick, style }) => {
  return (
    <button 
      className="button-main"
      onClick={onClick}
    >
      {text}
    </button>
  );
};

export default Button;