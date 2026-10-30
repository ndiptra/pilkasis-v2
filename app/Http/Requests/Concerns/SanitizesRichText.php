<?php

namespace App\Http\Requests\Concerns;

trait SanitizesRichText
{
    /**
     * @param  array<int, string>  $fields
     */
    protected function sanitizeRichTextFields(array $fields): void
    {
        $cleaned = [];

        foreach ($fields as $field) {
            $value = $this->input($field);

            if (! is_string($value)) {
                continue;
            }

            $value = strip_tags($value, '<p><br><hr><ul><ol><li><strong><b><em><i><u><s><strike><span><h1><h2><h3><h4><h5><h6><blockquote><pre>');
            $value = preg_replace_callback('/<(\w+)\s([^>]*)>/', function (array $match): string {
                $style = preg_match('/text-align:\s*(left|right|center|justify)/i', $match[2], $alignment)
                    ? ' style="text-align: '.strtolower($alignment[1]).';"'
                    : '';

                return "<{$match[1]}{$style}>";
            }, $value);
            $textOnly = trim(html_entity_decode(strip_tags($value)));

            $cleaned[$field] = $textOnly === '' ? null : $value;
        }

        $this->merge($cleaned);
    }
}
