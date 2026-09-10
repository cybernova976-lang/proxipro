<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManagedEmailContent
{
    public static function read(Request $request, array $existing = []): ?array
    {
        if (! $request->has('content_layout')) {
            return null;
        }
        $request->validate([
            'images' => ['nullable', 'array', 'list', 'max:3'],
            'images.*' => ['image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);
        $raw = $request->input('content_layout');
        if (! is_string($raw) || strlen($raw) > 50000) {
            throw ValidationException::withMessages(['content_layout' => 'Le contenu du message est invalide.']);
        }
        $blocks = json_decode($raw, true);
        Validator::make(['blocks' => $blocks], [
            'blocks' => ['required', 'array', 'min:1', 'max:30'],
            'blocks.*' => ['array:type,text,ref,width,align,caption'],
            'blocks.*.type' => ['required', Rule::in(['text', 'image'])],
            'blocks.*.text' => ['nullable', 'string', 'max:5000'],
            'blocks.*.ref' => ['nullable', 'string', 'max:40'],
            'blocks.*.width' => ['nullable', 'integer', 'min:80', 'max:560'],
            'blocks.*.align' => ['nullable', Rule::in(['left', 'center', 'right'])],
            'blocks.*.caption' => ['nullable', 'string', 'max:200'],
        ])->validate();
        $normalized = [];
        $imageRefs = [];
        $text = [];
        foreach ($blocks as $block) {
            if ($block['type'] === 'text') {
                $value = trim($block['text'] ?? '');
                if ($value !== '') {
                    $normalized[] = ['type' => 'text', 'text' => $value];
                    $text[] = $value;
                }
                continue;
            }
            $ref = $block['ref'] ?? '';
            if (! preg_match('/^(new|existing):(\d+)$/', $ref, $match)
                || ($match[1] === 'new' ? ! $request->hasFile('images.'.$match[2]) : ! isset($existing[(int) $match[2]]))) {
                throw ValidationException::withMessages(['images' => 'Une photo manque. Sélectionnez-la à nouveau.']);
            }
            $imageRefs[] = $ref;
            $normalized[] = ['type' => 'image', 'ref' => $ref, 'width' => (int) ($block['width'] ?? 560),
                'align' => $block['align'] ?? 'center', 'caption' => $block['caption'] ?? ''];
        }
        if (count($imageRefs) > 3 || count(array_unique($imageRefs)) !== count($imageRefs)) {
            throw ValidationException::withMessages(['images' => 'Utilisez au maximum trois photos distinctes.']);
        }
        if (count(array_filter($imageRefs, fn ($ref) => str_starts_with($ref, 'new:'))) !== count($request->file('images', []))) {
            throw ValidationException::withMessages(['images' => 'Chaque photo ajoutée doit correspondre à un bloc du message.']);
        }
        $body = implode("\n\n", $text);
        if ($body === '' || mb_strlen($body) > 5000) {
            throw ValidationException::withMessages(['body' => 'Ajoutez du texte au message (5 000 caractères maximum au total).']);
        }
        $request->merge(['body' => $body]);
        return $normalized;
    }

    public static function resolve(array $blocks, array $uploaded, array $existing = []): array
    {
        return array_map(function ($block) use ($uploaded, $existing) {
            if ($block['type'] === 'image') {
                [$kind, $index] = explode(':', $block['ref']);
                $block['path'] = $kind === 'new' ? $uploaded[(int) $index] : $existing[(int) $index];
                unset($block['ref']);
            }
            return $block;
        }, $blocks);
    }
}
