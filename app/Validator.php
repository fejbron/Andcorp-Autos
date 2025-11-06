<?php

/**
 * Input validation class
 */
class Validator {
    
    private $errors = [];
    
    /**
     * Validate required field
     */
    public function required($field, $value, $message = null) {
        if (empty($value) && $value !== '0') {
            $this->errors[$field] = $message ?? ucfirst($field) . ' is required';
        }
        return $this;
    }
    
    /**
     * Validate email
     */
    public function email($field, $value, $message = null) {
        if (!empty($value) && !Security::validateEmail($value)) {
            $this->errors[$field] = $message ?? 'Please enter a valid email address';
        }
        return $this;
    }
    
    /**
     * Validate string length
     */
    public function maxLength($field, $value, $max, $message = null) {
        if (!empty($value) && mb_strlen($value) > $max) {
            $this->errors[$field] = $message ?? ucfirst($field) . " must not exceed {$max} characters";
        }
        return $this;
    }
    
    /**
     * Validate string min length
     */
    public function minLength($field, $value, $min, $message = null) {
        if (!empty($value) && mb_strlen($value) < $min) {
            $this->errors[$field] = $message ?? ucfirst($field) . " must be at least {$min} characters";
        }
        return $this;
    }
    
    /**
     * Validate integer
     */
    public function integer($field, $value, $message = null) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field] = $message ?? ucfirst($field) . ' must be a valid integer';
        }
        return $this;
    }
    
    /**
     * Validate numeric
     */
    public function numeric($field, $value, $message = null) {
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field] = $message ?? ucfirst($field) . ' must be a valid number';
        }
        return $this;
    }
    
    /**
     * Validate range
     */
    public function range($field, $value, $min, $max, $message = null) {
        if (!empty($value)) {
            $num = (float)$value;
            if ($num < $min || $num > $max) {
                $this->errors[$field] = $message ?? ucfirst($field) . " must be between {$min} and {$max}";
            }
        }
        return $this;
    }
    
    /**
     * Validate enum
     */
    public function in($field, $value, array $allowed, $message = null) {
        if (!empty($value) && !in_array($value, $allowed, true)) {
            $this->errors[$field] = $message ?? ucfirst($field) . ' has an invalid value';
        }
        return $this;
    }
    
    /**
     * Validate password
     */
    public function password($field, $value, $message = null) {
        if (!empty($value)) {
            $result = Security::validatePassword($value);
            if ($result !== true) {
                $this->errors[$field] = $message ?? $result;
            }
        }
        return $this;
    }
    
    /**
     * Validate phone
     */
    public function phone($field, $value, $message = null) {
        if (!empty($value) && !Security::validatePhone($value)) {
            $this->errors[$field] = $message ?? 'Please enter a valid phone number';
        }
        return $this;
    }
    
    /**
     * Validate URL
     */
    public function url($field, $value, $message = null) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field] = $message ?? 'Please enter a valid URL';
        }
        return $this;
    }
    
    /**
     * Validate date
     */
    public function date($field, $value, $format = 'Y-m-d', $message = null) {
        if (!empty($value)) {
            $d = DateTime::createFromFormat($format, $value);
            if (!$d || $d->format($format) !== $value) {
                $this->errors[$field] = $message ?? 'Please enter a valid date';
            }
        }
        return $this;
    }
    
    /**
     * Validate match (e.g., password confirmation)
     */
    public function match($field, $value, $matchValue, $message = null) {
        if (!empty($value) && $value !== $matchValue) {
            $this->errors[$field] = $message ?? 'Values do not match';
        }
        return $this;
    }
    
    /**
     * Get errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails() {
        return !empty($this->errors);
    }
}

