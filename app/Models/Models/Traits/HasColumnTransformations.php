<?php

namespace App\Models\Traits;

/**
 * Trait HasColumnTransformations
 * 
 * Automatically transform column values during create/update
 * 
 * Usage in Model:
 * 
 * use HasColumnTransformations;
 * 
 * protected $columnTransformations = [
 *     'code' => 'uppercase_alphanumeric_dash',
 *     'slug' => 'lowercase_alphanumeric_dash',
 *     'name' => 'title_case',
 *     'email' => 'lowercase',
 *     'custom_field' => ['regex' => '/[^A-Z0-9]/', 'replacement' => '']
 * ];
 */
trait HasColumnTransformations
{
    protected static function bootHasColumnTransformations(): void
    {
        // Apply transformations before creating
        static::creating(function ($model) {
            $model->applyColumnTransformations();
        });

        // Apply transformations before updating
        static::updating(function ($model) {
            $model->applyColumnTransformations();
        });
    }

    /**
     * Apply all column transformations
     */
    protected function applyColumnTransformations(): void
    {
        if (empty($this->columnTransformations)) {
            return;
        }

        foreach ($this->columnTransformations as $column => $transformation) {
            if ($this->isFillable($column) && $this->{$column} !== null) {
                $this->{$column} = $this->transformValue($this->{$column}, $transformation);
            }
        }
    }

    /**
     * Transform a value based on transformation type
     */
    protected function transformValue($value, $transformation)
    {
        // Handle array-based custom transformations
        if (is_array($transformation)) {
            return $this->applyCustomTransformation($value, $transformation);
        }

        // Handle string-based predefined transformations
        return match ($transformation) {
            // Uppercase transformations
            'uppercase' => strtoupper($value),
            'uppercase_alphanumeric' => $this->uppercaseAlphanumeric($value),
            'uppercase_alphanumeric_dash' => $this->uppercaseAlphanumericDash($value),
            'uppercase_alphanumeric_underscore' => $this->uppercaseAlphanumericUnderscore($value),
            'uppercase_alphanumeric_dash_underscore' => $this->uppercaseAlphanumericDashUnderscore($value),

            // Lowercase transformations
            'lowercase' => strtolower($value),
            'lowercase_alphanumeric' => $this->lowercaseAlphanumeric($value),
            'lowercase_alphanumeric_dash' => $this->lowercaseAlphanumericDash($value),
            'lowercase_alphanumeric_underscore' => $this->lowercaseAlphanumericUnderscore($value),
            'lowercase_alphanumeric_dash_underscore' => $this->lowercaseAlphanumericDashUnderscore($value),
            'lowercase_alphanumeric_dash_dot' => $this->lowercaseAlphanumericDashDot($value),

            // Title/Sentence case
            'title_case' => $this->titleCase($value),
            'sentence_case' => $this->sentenceCase($value),
            'capitalize_first' => ucfirst($value),

            // Alphanumeric only
            'alphanumeric' => $this->alphanumeric($value),
            'numeric' => $this->numeric($value),
            'alpha' => $this->alpha($value),

            // Special formats
            // 'slug' => \Illuminate\Support\Str::slug($value),
            'snake_case' => $this->snakeCase($value),
            'kebab_case' => $this->kebabCase($value),
            'camel_case' => $this->camelCase($value),
            'pascal_case' => $this->pascalCase($value),

            // Trimming
            'trim' => trim($value),
            'trim_spaces' => $this->trimSpaces($value),

            default => $value,
        };
    }

    /**
     * Apply custom regex-based transformation
     */
    protected function applyCustomTransformation($value, array $config)
    {
        if (isset($config['regex']) && isset($config['replacement'])) {
            return preg_replace($config['regex'], $config['replacement'], $value);
        }

        if (isset($config['callback']) && is_callable($config['callback'])) {
            return call_user_func($config['callback'], $value);
        }

        return $value;
    }

    // UPPERCASE TRANSFORMATIONS
    protected function uppercaseAlphanumeric($value): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9]/', '', $value);
        return strtoupper($cleaned);
    }

    protected function uppercaseAlphanumericDash($value): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9\-]/', '', $value);
        return strtoupper($cleaned);
    }

    protected function uppercaseAlphanumericUnderscore($value): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9_]/', '', $value);
        return strtoupper($cleaned);
    }

    protected function uppercaseAlphanumericDashUnderscore($value): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9\-_]/', '', $value);
        return strtoupper($cleaned);
    }

    // LOWERCASE TRANSFORMATIONS
    protected function lowercaseAlphanumeric($value): string
    {
        return strtolower(preg_replace('/[^A-Za-z0-9]/', '', $value));
    }

    protected function lowercaseAlphanumericDash($value): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9\-]/', '', $value);
        $cleaned = preg_replace('/-+/', '-', $cleaned);
        return strtolower(trim($cleaned, '-'));
    }

    protected function lowercaseAlphanumericUnderscore($value): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9_]/', '', $value);
        $cleaned = preg_replace('/_+/', '_', $cleaned);
        return strtolower(trim($cleaned, '_'));
    }

    protected function lowercaseAlphanumericDashUnderscore($value): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9\-_]/', '', $value);
        return strtolower($cleaned);
    }

    protected function lowercaseAlphanumericDashDot($value): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9\-\.]/', '', $value);
        return strtolower($cleaned);
    }

    // CASE TRANSFORMATIONS
    protected function titleCase($value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    protected function sentenceCase($value): string
    {
        return ucfirst(strtolower($value));
    }

    // ALPHANUMERIC TRANSFORMATIONS
    protected function alphanumeric($value): string
    {
        return preg_replace('/[^A-Za-z0-9]/', '', $value);
    }

    protected function numeric($value): string
    {
        return preg_replace('/[^0-9]/', '', $value);
    }

    protected function alpha($value): string
    {
        return preg_replace('/[^A-Za-z]/', '', $value);
    }

    // SPECIAL FORMATS
    protected function slug($value): string
    {
        // return \Illuminate\Support\Str::slug($value);
    }

    protected function snakeCase($value): string
    {
        return \Illuminate\Support\Str::snake($value);
    }

    protected function kebabCase($value): string
    {
        return \Illuminate\Support\Str::kebab($value);
    }

    protected function camelCase($value): string
    {
        return \Illuminate\Support\Str::camel($value);
    }

    protected function pascalCase($value): string
    {
        return \Illuminate\Support\Str::studly($value);
    }

    protected function trimSpaces($value): string
    {
        return preg_replace('/\s+/', ' ', trim($value));
    }
}
