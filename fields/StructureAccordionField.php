<?php

namespace Hananils\Fields;

use Kirby\Cms\Structure;
use Kirby\Cms\StructureObject;
use Kirby\Content\Content;
use Kirby\Content\Field;
use Kirby\Data\Data;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Form\Field\InputField;
use Kirby\Form\Interface\ProvidesNestedForm;
use Kirby\Form\Mixin;
use Kirby\Panel\Ui\Dialog\FormDialog;
use Kirby\Reflection\Attributes\Prop;
use Kirby\Toolkit\Str;

class StructureAccordionField extends InputField implements ProvidesNestedForm
{
    use Mixin\Batch;
    use Mixin\Duplicate;
    use Mixin\EmptyState;
    use Mixin\Fields;
    use Mixin\Max;
    use Mixin\Min;
    use Mixin\Prepend;
    use Mixin\Sortable;
    use Mixin\SortBy;

    protected array $value = [];

    /**
     * The number of entries that will be displayed on a single page. Afterwards pagination kicks in.
     */
    #[Prop]
    protected int|false|null $limit = false;

    #[Prop(export: false)]
    protected string|array|null $summary = null;

    #[Prop]
    protected array $data = [];

    public function data()
    {
        $items = $this->toItems($this->value);

        return $this->summarize($items);
    }

    public function fill(mixed $value): static
    {
        if (is_array($value)) {
            return parent::fill(value: $value);
        }

        if ($value instanceof Structure) {
            $value = $value->toArray(fn($row) => $row->content()->toArray());
        }

        return parent::fill(value: $this->toItems($value));
    }

    public function summary()
    {
        if ($this->summary === null || $this->summary === '') {
            return ['text' => $this->summary];
        }

        return $this->summary;
    }

    public function toItems($value): array
    {
        // Handle empty values, return early
        if (empty($value)) {
            return [];
        }

        // Get data and filter out non-array items
        $items = array_filter(Data::decode($value, 'yaml'), 'is_array');

        // Handle empty data, return early
        if (empty($items) || !is_array($items)) {
            return [];
        }

        // Make sure original language has UUIDs for all items
        if (kirby()->language()->isDefault()) {
            $items = array_map([$this, 'ensureUuids'], $items);
        }

        // Normalize translations:
        // Using primary language as base, matching translations by UUID,
        // ignoring translated item without matching original.
        if (!kirby()->language()->isDefault()) {
            $primary = $this->model()
                ->content('default')
                ->get($this->name)
                ->yaml();
            $items = $this->matchTranslations($items, $primary);
        }

        // Sort data
        $items = $this->sort($items, $this->sortBy);

        // Create items
        return array_map(function ($item) {
            return $this->form()
                ->fill(input: $item, passthrough: true)
                ->toFormValues();
        }, $items);
    }

    public function ensureUuids(array $item)
    {
        if (!array_key_exists('uuid', $item)) {
            $item['uuid'] = Str::uuid();
        }

        return $item;
    }

    public function toFormValue(): array
    {
        $data = [];

        foreach ($this->value as $row) {
            $data[] = $this->form()
                ->fill(input: $row, passthrough: true)
                ->toFormValues();
        }

        return $data;
    }

    public function toStoredValue(): array
    {
        $data = [];
        $form = $this->form();
        $defaults = $form->defaults();

        foreach ($this->value as $row) {
            if (!array_key_exists('uuid', $row)) {
                $row['uuid'] = Str::uuid();
            }

            $row = $form
                ->reset()
                ->fill(input: $defaults)
                ->submit(input: $row, passthrough: true)
                ->toStoredValues();

            $data[] = $row;
        }

        return $data;
    }

    public function api(): array
    {
        $field = $this;

        return [
            [
                'pattern' => 'summarize',
                'method' => 'POST',
                'action' => fn() => [
                    'summaries' => $field->summarize(get('items', []))
                ]
            ],
            [
                'pattern' => 'synchronize',
                'method' => 'GET',
                'action' => fn() => $field->synchronize()
            ],
            [
                'pattern' => 'replace',
                'method' => 'POST',
                'action' => fn() => [
                    'items' => $field->parsePlaceholders(
                        get('selected'),
                        get('fields')
                    )
                ]
            ]
        ];
    }

    public function parsePlaceholders(
        array $selected = [],
        array $fields = []
    ): array {
        foreach ($selected as $index => $item) {
            foreach ($fields as $name => $updated) {
                if (!str_contains($updated, '{{')) {
                    $selected[$index][$name] = $updated;
                    continue;
                }

                $field = new Field($this->model(), $name, $item[$name]);
                $structureItem = new StructureObject([
                    'content' => $item,
                    'parent' => $this->model()
                ]);

                $selected[$index][$name] = Str::template($updated, [
                    'field' => $field,
                    'item' => $structureItem,
                    'index' => $index,
                    'position' => $index + 1
                ]);
            }
        }

        return $selected;
    }

    public function synchronize()
    {
        $page = $this->model();
        $primary = $page->content('default')->get($this->name)->yaml();

        foreach (kirby()->languages() as $language) {
            if ($language->isDefault()) {
                continue;
            }

            // @todo: Check if translations is locked!

            $code = $language->code();
            $translation = $page->content($code)->get($this->name)->yaml();
            $matched = $this->matchTranslations($translation, $primary);

            $page->update([$this->name => $matched], $code);
        }

        return true;
    }

    public function templates()
    {
        $template = [
            'label' => $this->summary['label'] ?? null,
            'text' => $this->summary['text'] ?? null,
            'info' => $this->summary['info'] ?? null,
            'extra' => $this->summary['extra'] ?? null,
            'empty' =>
                $this->summary['empty'] ??
                t('hananils.structure-accordion.empty')
        ];

        if ($template['text'] === null) {
            $first = array_key_first($this->fields);
            $template['text'] = "{{ item.$first }}";
        }

        return array_filter($template);
    }

    public function sort(array $array, null|string $sortBy = '')
    {
        if (!$sortBy || $sortBy === '') {
            return $array;
        }

        [$field, $direction] = explode(' ', $sortBy);

        usort($array, function ($a, $b) use ($field) {
            return strnatcasecmp($a[$field], $b[$field]);
        });

        if ($direction !== 'asc') {
            return array_reverse($array);
        }

        return $array;
    }

    public function summarize(array $items = [])
    {
        if ($items === []) {
            return [];
        }

        $templates = $this->templates();
        $summaries = [];

        if ($items) {
            foreach ($items as $item) {
                $uuid = $item['uuid'];

                // Create summaries: apply templates, remove markup
                $summaries[$uuid] = array_map(function ($template) use ($item) {
                    return trim(
                        strip_tags(
                            Str::template($template, [
                                'item' => new Content($item)
                            ])
                        )
                    );
                }, $templates);

                // Use fallback, if nothing got replaced
                if (
                    !isset($summaries[$uuid]['text']) ||
                    empty($summaries[$uuid]['text'])
                ) {
                    $summaries[$uuid]['text'] = $templates['empty'];
                }

                unset($summaries[$uuid]['empty']);
            }
        }

        return $summaries;
    }

    public function matchTranslations($data, $primary)
    {
        $uuids = array_column($data, 'uuid');

        if (empty($uuids)) {
            // No valid translations, use original language
            $data = $primary;
        } else {
            // Match translations to original data structure by UUID
            $data = array_map(function ($item) use ($data, $uuids) {
                // Find translation by UUID
                $uuid = $item['uuid'] ?? null;
                $key = array_search($uuid, $uuids);

                // Translation found!
                if ($key !== false) {
                    // Merge translation into original to keep
                    // untranslated content
                    return [...$item, ...$data[$key]];
                }

                return $item;
            }, $primary);
        }

        return $data;
    }

    protected function validations(): array
    {
        return ['min', 'max', 'structure' => $this->validateRows(...)];
    }

    protected function validateRows(array $value): void
    {
        if ($value === []) {
            return;
        }

        foreach ($this->value as $index => $value) {
            $form = $this->form()->fill(input: $value);

            foreach ($form->fields() as $field) {
                $errors = $field->errors();

                if ($errors !== []) {
                    throw new InvalidArgumentException(
                        key: 'structure.validation',
                        data: [
                            'field' =>
                                $field->label() ?? Str::label($field->name()),
                            'index' => $index + 1
                        ]
                    );
                }
            }
        }
    }

    public function dialogs(): array
    {
        return [
            'batch-edit' => fn() => new FormDialog(
                component: 'k-structure-accordion-edit',
                fields: $this->fields,
                submitButton: 'Update selected'
            )
        ];
    }
}
