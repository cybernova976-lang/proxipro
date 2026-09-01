<?php

namespace App\Support;

use Carbon\Carbon;

class ServiceDemandIntakeSchema
{
    public static function forCategory(?string $mainCategory): array
    {
        $profileKey = config('service_demand_intake.category_profiles', [])[$mainCategory] ?? null;

        if (! $profileKey) {
            return [];
        }

        return config('service_demand_intake.profiles.'.$profileKey, []);
    }

    public static function forForm(array $mainCategories): array
    {
        $schemas = [];

        foreach ($mainCategories as $mainCategory) {
            $schema = self::forCategory($mainCategory);
            if ($schema !== []) {
                $schemas[$mainCategory] = $schema;
            }
        }

        return $schemas;
    }

    public static function validationRules(?string $mainCategory): array
    {
        $schema = self::forCategory($mainCategory);
        $fields = $schema['fields'] ?? [];

        if ($fields === []) {
            return ['service_details' => ['nullable', 'array']];
        }

        $rules = ['service_details' => ['required', 'array']];

        foreach ($fields as $key => $field) {
            $fieldRules = [($field['required'] ?? false) ? 'required' : 'nullable'];
            $type = $field['type'] ?? 'text';

            if ($type === 'select') {
                $fieldRules[] = 'string';
                $fieldRules[] = 'in:'.implode(',', array_keys($field['options'] ?? []));
            } elseif ($type === 'number') {
                $fieldRules[] = 'numeric';
                if (isset($field['min'])) {
                    $fieldRules[] = 'min:'.$field['min'];
                }
                if (isset($field['max'])) {
                    $fieldRules[] = 'max:'.$field['max'];
                }
            } else {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:'.($field['maxlength'] ?? 255);
            }

            $rules['service_details.'.$key] = $fieldRules;
        }

        return $rules;
    }

    public static function validationAttributes(?string $mainCategory): array
    {
        $attributes = [];

        foreach (self::forCategory($mainCategory)['fields'] ?? [] as $key => $field) {
            $attributes['service_details.'.$key] = mb_strtolower($field['label'] ?? $key);
        }

        return $attributes;
    }

    public static function sanitize(?string $mainCategory, mixed $details): array
    {
        $details = is_array($details) ? $details : [];
        $sanitized = [];

        foreach (self::forCategory($mainCategory)['fields'] ?? [] as $key => $field) {
            if (! array_key_exists($key, $details)) {
                continue;
            }

            $value = is_string($details[$key]) ? trim($details[$key]) : $details[$key];
            if ($value === '' || $value === null) {
                continue;
            }

            if (($field['type'] ?? null) === 'select'
                && ! array_key_exists((string) $value, $field['options'] ?? [])) {
                continue;
            }

            $sanitized[$key] = ($field['type'] ?? null) === 'number' ? (float) $value : $value;
        }

        return $sanitized;
    }

    public static function presentationDetails(?string $mainCategory, mixed $adDetails): array
    {
        $adDetails = is_array($adDetails) ? $adDetails : [];
        $rows = [];

        if (! empty($adDetails['desired_date'])) {
            try {
                $date = Carbon::parse($adDetails['desired_date'])->locale('fr')->translatedFormat('d F Y');
            } catch (\Throwable) {
                $date = (string) $adDetails['desired_date'];
            }
            $rows[] = ['key' => 'desired_date', 'label' => 'Date souhaitée', 'value' => $date];
        }

        $timeWindows = [
            'flexible' => 'Flexible',
            'morning' => 'Matin',
            'afternoon' => 'Après-midi',
            'evening' => 'Soir',
        ];
        if (! empty($adDetails['time_window'])) {
            $rows[] = [
                'key' => 'time_window',
                'label' => 'Moment de la journée',
                'value' => $timeWindows[$adDetails['time_window']] ?? (string) $adDetails['time_window'],
            ];
        }

        $urgencies = ['normal' => 'Normal', 'urgent' => 'Urgent', 'tres_urgent' => 'Très urgent'];
        if (! empty($adDetails['urgency'])) {
            $rows[] = [
                'key' => 'urgency',
                'label' => 'Priorité',
                'value' => $urgencies[$adDetails['urgency']] ?? (string) $adDetails['urgency'],
            ];
        }

        $answers = is_array($adDetails['service_details'] ?? null) ? $adDetails['service_details'] : [];
        foreach (self::forCategory($mainCategory)['fields'] ?? [] as $key => $field) {
            if (! array_key_exists($key, $answers) || $answers[$key] === '' || $answers[$key] === null) {
                continue;
            }

            $value = $answers[$key];
            if (($field['type'] ?? null) === 'select') {
                $value = $field['options'][$value] ?? $value;
            }

            $rows[] = [
                'key' => 'service_details.'.$key,
                'label' => $field['label'] ?? $key,
                'value' => (string) $value,
            ];
        }

        return $rows;
    }
}
