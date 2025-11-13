<?php

/**
 * ValidationException - Custom exception for validation errors
 */
class ValidationException extends Exception {
    private $errors = [];

    public function __construct($message = "", $errors = [], $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors() {
        return $this->errors;
    }
}