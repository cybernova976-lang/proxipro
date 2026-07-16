<?php

namespace App\Services;

use Carbon\Carbon;

class AdPublicationSchema
{
    public function resolve(?string $mainCategory, ?string $category): array
    {
        $categories = array_merge(
            config('categories.services', []),
            config('categories.marketplace', [])
        );

        $resolvedMainCategory = null;

        if ($mainCategory && isset($categories[$mainCategory])) {
            $subcategories = $categories[$mainCategory]['subcategories'] ?? [];
            if (! $category || in_array($category, $subcategories, true) || $category === $mainCategory) {
                $resolvedMainCategory = $mainCategory;
            }
        }

        if (! $resolvedMainCategory && $category) {
            foreach ($categories as $name => $definition) {
                if ($category === $name || in_array($category, $definition['subcategories'] ?? [], true)) {
                    $resolvedMainCategory = $name;
                    break;
                }
            }
        }

        $domain = config(
            'ad_publication.category_domains.'.$resolvedMainCategory,
            config('ad_publication.default_domain', 'service')
        );

        return [
            'main_category' => $resolvedMainCategory,
            'domain' => $domain,
        ];
    }

    public function schemasForForm(): array
    {
        $schemas = config('ad_publication.domains', []);
        $categoryDomains = config('ad_publication.category_domains', []);

        foreach ($schemas as $domain => &$schema) {
            $schema['main_categories'] = array_keys(array_filter(
                $categoryDomains,
                fn (string $mappedDomain): bool => $mappedDomain === $domain
            ));

            if ($domain === config('ad_publication.default_domain', 'service')) {
                $schema['is_default'] = true;
            }

            foreach ($schema['fields'] ?? [] as &$field) {
                unset($field['rules']);
            }
            unset($field);
        }
        unset($schema);

        return $schemas;
    }

    public function validationRules(string $domain): array
    {
        $allowedPriceTypes = config('ad_publication.domains.'.$domain.'.price.allowed_types', ['fixed', 'hourly', 'negotiable']);
        $rules = [
            'price_type' => ['required', 'in:'.implode(',', $allowedPriceTypes)],
            'ad_details' => ['nullable', 'array'],
        ];
        $fields = config('ad_publication.domains.'.$domain.'.fields', []);

        foreach ($fields as $key => $field) {
            $rules['ad_details.'.$key] = $field['rules'] ?? ['nullable'];
        }

        return $rules;
    }

    public function validationAttributes(string $domain): array
    {
        $attributes = [];

        foreach (config('ad_publication.domains.'.$domain.'.fields', []) as $key => $field) {
            $attributes['ad_details.'.$key] = mb_strtolower($field['label'] ?? $key);
        }

        return $attributes;
    }

    public function sanitizeDetails(string $domain, mixed $details): array
    {
        $details = is_array($details) ? $details : [];
        $sanitized = [];

        foreach (config('ad_publication.domains.'.$domain.'.fields', []) as $key => $field) {
            if (($field['type'] ?? null) === 'checkbox') {
                $sanitized[$key] = filter_var($details[$key] ?? false, FILTER_VALIDATE_BOOL);

                continue;
            }

            if (! array_key_exists($key, $details)) {
                continue;
            }

            $value = $details[$key];
            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value !== '' && $value !== null) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    public function presentationDetails(string $domain, mixed $details): array
    {
        $details = is_array($details) ? $details : [];
        $rows = [];

        foreach (config('ad_publication.domains.'.$domain.'.fields', []) as $key => $field) {
            if (! array_key_exists($key, $details) || $details[$key] === '' || $details[$key] === null) {
                continue;
            }

            $value = $details[$key];
            $type = $field['type'] ?? 'text';

            if ($type === 'select') {
                $value = $field['options'][$value] ?? $value;
            } elseif ($type === 'checkbox') {
                $value = $value ? 'Oui' : 'Non';
            } elseif (in_array($type, ['date', 'datetime-local'], true)) {
                try {
                    $value = Carbon::parse($value)
                        ->locale('fr')
                        ->translatedFormat($type === 'date' ? 'd F Y' : 'd F Y à H:i');
                } catch (\Throwable) {
                    // Conserver la valeur historique si elle ne peut pas être interprétée.
                }
            } elseif ($key === 'deposit') {
                $value = number_format((float) $value, 2, ',', ' ').' €';
            }

            $rows[] = [
                'key' => $key,
                'label' => $field['label'] ?? $key,
                'value' => (string) $value,
            ];
        }

        return $rows;
    }
}
