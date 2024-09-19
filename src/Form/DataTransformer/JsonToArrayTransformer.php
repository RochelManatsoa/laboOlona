<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class JsonToArrayTransformer implements DataTransformerInterface
{
    public function transform($value): mixed
    {
        // Transform array to JSON string expected by Tagify
        if ($value === null) {
            return '';
        }

        $tagifyData = array_map(function($item) {
            if (is_string($item)) {
                return ['value' => $item];
            }
            return $item; // In case item is already in expected format
        }, $value);

        return json_encode($tagifyData);
    }

    public function reverseTransform($value): mixed
    {
        // Transform Tagify JSON string to plain array
        if ($value === '') {
            return [];
        }

        $jsonData = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return array_map(function($item) {
            return $item['value'];
        }, $jsonData);
    }
}