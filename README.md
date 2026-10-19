# WIP: Kirby Structure Accordion

> [!IMPORTANT]
> Work in progress: do not use in production.

## Example blueprint

```yaml
structure:
  type: structureaccordion
  label: Structure Accordion
  help: Your help text here.
  batch: true
  sortable: true
  summary:
    label: 'This is the label'
    text: 'This is the text'
    empty: 'Ohne Beschreibung'
    info: 'This is the info'
    extra: 'This is the extra content area for longer summaries.'
  fields:
    text:
      type: text
      label: Text
    description:
      type: textarea
      label: Description
```

- **summary**: content used to give an overview of the item, use Kirby query language to fill those fields.
- **batch**: enabled batch editing
- **fields**: like for normal structures

> [!WARNING]
> In multilingual contexts, the primary language is the only one where you can make structural changes (add, delete, sort). Structural changes are synchronized to secondary languages on save. So if you delete an item in your primary language, it will also be gone in your secondary languages.

> [!WARNING]
> The field adds a hidden UUID to each item of the structure. This is used to synchronize structural changes across languages. If you have existing content, you might loose your translations on first save.
