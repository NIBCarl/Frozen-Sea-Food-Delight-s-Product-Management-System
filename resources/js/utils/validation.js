/**
 * Centralized validation rules for forms
 */

// Basic validation rules
export const rules = {
  // Required field
  required: (fieldName = 'Field') => value => !!value || `${fieldName} is required.`,
  
  // Email validation
  email: value => {
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return pattern.test(value) || 'Please enter a valid email address.';
  },
  
  // Numeric validation
  numeric: value => {
    if (!value) return true; // Allow empty for optional fields
    return !isNaN(parseFloat(value)) && isFinite(value) || 'Must be a valid number.';
  },
  
  // Integer validation
  integer: value => {
    if (!value) return true; // Allow empty for optional fields
    return Number.isInteger(Number(value)) || 'Must be a whole number.';
  },
  
  // Positive number validation
  positive: value => {
    if (!value) return true; // Allow empty for optional fields
    return parseFloat(value) > 0 || 'Must be greater than 0.';
  },
  
  // Minimum length validation
  minLength: (min) => value => {
    if (!value) return true; // Allow empty for optional fields
    return value.length >= min || `Must be at least ${min} characters long.`;
  },
  
  // Maximum length validation
  maxLength: (max) => value => {
    if (!value) return true; // Allow empty for optional fields
    return value.length <= max || `Must be no more than ${max} characters long.`;
  },
  
  // Password validation (strong password)
  password: value => {
    if (!value) return true;
    const minLength = 8;
    const hasUpper = /[A-Z]/.test(value);
    const hasLower = /[a-z]/.test(value);
    const hasNumber = /\d/.test(value);
    const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(value);
    
    if (value.length < minLength) {
      return `Password must be at least ${minLength} characters long.`;
    }
    if (!hasUpper) {
      return 'Password must contain at least one uppercase letter.';
    }
    if (!hasLower) {
      return 'Password must contain at least one lowercase letter.';
    }
    if (!hasNumber) {
      return 'Password must contain at least one number.';
    }
    if (!hasSpecial) {
      return 'Password must contain at least one special character.';
    }
    return true;
  },
  
  // Password confirmation
  passwordConfirmation: (originalPassword) => value => {
    return value === originalPassword || 'Passwords do not match.';
  },
  
  // Phone number validation
  phone: value => {
    if (!value) return true; // Allow empty for optional fields
    const pattern = /^[\+]?[1-9][\d]{0,15}$/;
    return pattern.test(value.replace(/[\s\-\(\)]/g, '')) || 'Please enter a valid phone number.';
  },
  
  // URL validation
  url: value => {
    if (!value) return true; // Allow empty for optional fields
    try {
      new URL(value);
      return true;
    } catch {
      return 'Please enter a valid URL.';
    }
  },
  
  // Date validation
  date: value => {
    if (!value) return true; // Allow empty for optional fields
    const date = new Date(value);
    return !isNaN(date.getTime()) || 'Please enter a valid date.';
  },
  
  // Price validation (two decimal places)
  price: value => {
    if (!value) return true; // Allow empty for optional fields
    const pattern = /^\d+(\.\d{1,2})?$/;
    return pattern.test(value.toString()) || 'Please enter a valid price (max 2 decimal places).';
  },
  
  // Array required (for multiple selects)
  requiredArray: (fieldName = 'Field') => value => {
    return (Array.isArray(value) && value.length > 0) || `${fieldName} is required.`;
  },
  
  // File size validation (in MB)
  maxFileSize: (maxSizeMB) => value => {
    if (!value) return true; // Allow empty for optional fields
    if (Array.isArray(value)) {
      for (const file of value) {
        if (file.size > maxSizeMB * 1024 * 1024) {
          return `File size must not exceed ${maxSizeMB}MB.`;
        }
      }
      return true;
    } else if (value.size) {
      return value.size <= maxSizeMB * 1024 * 1024 || `File size must not exceed ${maxSizeMB}MB.`;
    }
    return true;
  },
  
  // Image file validation
  imageFile: value => {
    if (!value) return true; // Allow empty for optional fields
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    
    if (Array.isArray(value)) {
      for (const file of value) {
        if (!allowedTypes.includes(file.type)) {
          return 'Only image files (JPEG, PNG, GIF, WebP) are allowed.';
        }
      }
      return true;
    } else if (value.type) {
      return allowedTypes.includes(value.type) || 'Only image files (JPEG, PNG, GIF, WebP) are allowed.';
    }
    return true;
  },
  
  // SKU/Code validation (alphanumeric with hyphens and underscores)
  sku: value => {
    if (!value) return true; // Allow empty for optional fields
    const pattern = /^[A-Za-z0-9\-_]+$/;
    return pattern.test(value) || 'SKU can only contain letters, numbers, hyphens, and underscores.';
  },
};

// Composite validation rules (combinations of basic rules)
export const compositeRules = {
  // Product name validation
  productName: [
    rules.required('Product name'),
    rules.minLength(2),
    rules.maxLength(255)
  ],
  
  // Category name validation
  categoryName: [
    rules.required('Category name'),
    rules.minLength(2),
    rules.maxLength(100)
  ],
  
  // User email validation
  userEmail: [
    rules.required('Email'),
    rules.email
  ],
  
  // User password validation
  userPassword: [
    rules.required('Password'),
    rules.password
  ],
  
  // Product price validation
  productPrice: [
    rules.required('Price'),
    rules.numeric,
    rules.positive,
    rules.price
  ],
  
  // Stock quantity validation
  stockQuantity: [
    rules.required('Stock quantity'),
    rules.integer,
    (value) => parseInt(value) >= 0 || 'Stock quantity cannot be negative.'
  ],
  
  // Product images validation
  productImages: [
    rules.imageFile,
    rules.maxFileSize(5) // 5MB limit
  ],
};

// Validation utility functions
export const validationUtils = {
  // Validate a single field against multiple rules
  validateField(value, rulesList) {
    for (const rule of rulesList) {
      const result = rule(value);
      if (result !== true) {
        return result; // Return first error message
      }
    }
    return true;
  },
  
  // Validate an entire form object
  validateForm(formData, validationSchema) {
    const errors = {};
    let isValid = true;
    
    for (const [fieldName, rules] of Object.entries(validationSchema)) {
      const value = formData[fieldName];
      const result = this.validateField(value, rules);
      
      if (result !== true) {
        errors[fieldName] = result;
        isValid = false;
      }
    }
    
    return { isValid, errors };
  },
  
  // Create a validation schema for forms
  createSchema(fields) {
    return fields;
  },
  
  // Format validation errors for display
  formatErrors(errors) {
    if (typeof errors === 'string') {
      return [errors];
    }
    
    if (Array.isArray(errors)) {
      return errors;
    }
    
    if (typeof errors === 'object') {
      return Object.values(errors).flat();
    }
    
    return ['Unknown validation error'];
  },
};

// Pre-defined validation schemas for common forms
export const schemas = {
  product: {
    name: compositeRules.productName,
    category_id: [rules.required('Category')],
    price: compositeRules.productPrice,
    stock_quantity: compositeRules.stockQuantity,
    min_stock_level: [rules.integer, (value) => parseInt(value) >= 0 || 'Minimum stock level cannot be negative.'],
    sku: [rules.sku],
    status: [rules.required('Status')],
  },
  
  category: {
    name: compositeRules.categoryName,
    status: [rules.required('Status')],
    sort_order: [rules.integer, (value) => parseInt(value) >= 0 || 'Sort order cannot be negative.'],
  },
  
  user: {
    name: [rules.required('Name'), rules.minLength(2), rules.maxLength(255)],
    username: [rules.required('Username'), rules.minLength(3), rules.maxLength(50)],
    email: compositeRules.userEmail,
    password: [rules.minLength(8)], // Less strict for editing
    roles: [rules.requiredArray('Roles')],
  },
  
  stockMovement: {
    product_id: [rules.required('Product')],
    type: [rules.required('Movement type')],
    quantity: [rules.required('Quantity'), rules.integer, rules.positive],
  },
  
  auth: {
    login: {
      email: compositeRules.userEmail,
      password: [rules.required('Password')],
    },
    register: {
      name: [rules.required('Name'), rules.minLength(2), rules.maxLength(255)],
      username: [rules.required('Username'), rules.minLength(3), rules.maxLength(50)],
      email: compositeRules.userEmail,
      password: compositeRules.userPassword,
    }
  }
};

export default {
  rules,
  compositeRules,
  validationUtils,
  schemas,
};
