<?php
/**
 * validate.php &mdash; Shared validation helpers for Smart Dairy
 * Include this file in any page that needs input validation.
 */

class Validator {
    private array $errors = [];
    private array $data   = [];

    public function __construct(array $data) {
        $this->data = $data;
    }

    // ── Core validators ──────────────────────────────────────

    public function required(string $field, string $label): static {
        $val = trim($this->data[$field] ?? '');
        if ($val === '') $this->errors[$field] = "$label is required.";
        return $this;
    }

    public function minLength(string $field, int $min, string $label): static {
        $val = trim($this->data[$field] ?? '');
        if ($val !== '' && mb_strlen($val) < $min)
            $this->errors[$field] = "$label must be at least $min characters.";
        return $this;
    }

    public function maxLength(string $field, int $max, string $label): static {
        $val = trim($this->data[$field] ?? '');
        if (mb_strlen($val) > $max)
            $this->errors[$field] = "$label must not exceed $max characters.";
        return $this;
    }

    public function phone(string $field, string $label = 'Phone number'): static {
        $val = preg_replace('/\s+/', '', trim($this->data[$field] ?? ''));
        if ($val !== '' && !preg_match('/^[6-9][0-9]{9}$/', $val))
            $this->errors[$field] = "$label must be a valid 10-digit Indian mobile number (starts with 6-9).";
        return $this;
    }

    public function numeric(string $field, string $label): static {
        $val = trim($this->data[$field] ?? '');
        if ($val !== '' && !is_numeric($val))
            $this->errors[$field] = "$label must be a number.";
        return $this;
    }

    public function min(string $field, float $min, string $label): static {
        $val = trim($this->data[$field] ?? '');
        if (is_numeric($val) && (float)$val < $min)
            $this->errors[$field] = "$label must be at least $min.";
        return $this;
    }

    public function max(string $field, float $max, string $label): static {
        $val = trim($this->data[$field] ?? '');
        if (is_numeric($val) && (float)$val > $max)
            $this->errors[$field] = "$label must not exceed $max.";
        return $this;
    }

    public function between(string $field, float $min, float $max, string $label): static {
        return $this->min($field, $min, $label)->max($field, $max, $label);
    }

    public function notFutureDate(string $field, string $label = 'Date'): static {
        $val = trim($this->data[$field] ?? '');
        if ($val !== '' && strtotime($val) > strtotime(date('Y-m-d')))
            $this->errors[$field] = "$label cannot be a future date.";
        return $this;
    }

    public function validDate(string $field, string $label = 'Date'): static {
        $val = trim($this->data[$field] ?? '');
        if ($val !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $val);
            if (!$d || $d->format('Y-m-d') !== $val)
                $this->errors[$field] = "$label must be a valid date (YYYY-MM-DD).";
        }
        return $this;
    }

    public function name(string $field, string $label = 'Name'): static {
        $val = trim($this->data[$field] ?? '');
        if ($val !== '' && !preg_match('/^[\p{L}\s\.\-\']{2,100}$/u', $val))
            $this->errors[$field] = "$label can only contain letters, spaces, dots, hyphens.";
        return $this;
    }

    public function positiveInt(string $field, string $label): static {
        $val = trim($this->data[$field] ?? '');
        if ($val !== '' && (!ctype_digit($val) || (int)$val < 0))
            $this->errors[$field] = "$label must be a whole positive number.";
        return $this;
    }

    public function inList(string $field, array $allowed, string $label): static {
        $val = trim($this->data[$field] ?? '');
        if ($val !== '' && !in_array($val, $allowed, true))
            $this->errors[$field] = "Invalid value for $label.";
        return $this;
    }

    // ── Results ───────────────────────────────────────────────

    public function fails(): bool  { return !empty($this->errors); }
    public function passes(): bool { return empty($this->errors); }

    public function errors(): array { return $this->errors; }

    public function firstError(): string {
        return reset($this->errors) ?: '';
    }

    public function get(string $field, mixed $default = ''): mixed {
        return $this->data[$field] ?? $default;
    }

    public function clean(string $field): string {
        return trim($this->data[$field] ?? '');
    }
}

/**
 * Render field-level error message HTML.
 */
function fieldError(array $errors, string $field): string {
    if (isset($errors[$field])) {
        return '<div class="field-error"><span>&#x26A0;</span> ' . htmlspecialchars($errors[$field]) . '</div>';
    }
    return '';
}

/**
 * CSS class for invalid field.
 */
function fieldClass(array $errors, string $field, string $base = 'form-control'): string {
    return $base . (isset($errors[$field]) ? ' is-invalid' : '');
}
