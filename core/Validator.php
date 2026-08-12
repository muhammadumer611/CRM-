<?php
namespace Core;

class Validator {
    private $data;
    private $errors = [];

    public function __construct($data) {
        $this->data = $data;
    }

    public function validate($rules) {
        foreach ($rules as $field => $fieldRules) {
            $rulesArray = explode('|', $fieldRules);
            $value = $this->data[$field] ?? null;

            foreach ($rulesArray as $rule) {
                if ($rule === 'required' && ($value === null || trim((string)$value) === '')) {
                    $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                }
                
                if ($value !== null && $value !== '') {
                    if ($rule === 'string' && !is_string($value)) {
                        $this->addError($field, 'Must be a valid string.');
                    }
                    if ($rule === 'integer' && !filter_var($value, FILTER_VALIDATE_INT)) {
                        $this->addError($field, 'Must be a valid integer.');
                    }
                    if ($rule === 'numeric' && !is_numeric($value)) {
                        $this->addError($field, 'Must be a numeric value.');
                    }
                    if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->addError($field, 'Must be a valid email address.');
                    }
                    if (strpos($rule, 'max:') === 0) {
                        $max = (int)substr($rule, 4);
                        if (strlen((string)$value) > $max) {
                            $this->addError($field, "Maximum length is {$max} characters.");
                        }
                    }
                    if (strpos($rule, 'min:') === 0) {
                        $min = (int)substr($rule, 4);
                        if (strlen((string)$value) < $min) {
                            $this->addError($field, "Minimum length is {$min} characters.");
                        }
                    }
                    if ($rule === 'positive_number' && (is_numeric($value) && $value < 0)) {
                        $this->addError($field, 'Must be a positive number.');
                    }
                    if ($rule === 'pak_phone' && !preg_match('/^(03)[0-9]{2}-?[0-9]{7}$/', $value)) {
                        $this->addError($field, 'Must be a valid Pakistani phone number (e.g. 0300-1234567).');
                    }
                    if ($rule === 'cnic' && !preg_match('/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/', $value)) {
                        $this->addError($field, 'Must be a valid CNIC format (12345-1234567-1).');
                    }
                    if (strpos($rule, 'enum:') === 0) {
                        $options = explode(',', substr($rule, 5));
                        if (!in_array($value, $options)) {
                            $this->addError($field, "Must be one of: " . implode(', ', $options));
                        }
                    }
                    if ($rule === 'date') {
                        $d = \DateTime::createFromFormat('Y-m-d', $value);
                        if (!$d || $d->format('Y-m-d') !== $value) {
                            $this->addError($field, 'Must be a valid date in YYYY-MM-DD format.');
                        }
                    }
                }
            }
        }
        
        return empty($this->errors);
    }

    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function getErrors() {
        return $this->errors;
    }
}
