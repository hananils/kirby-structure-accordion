# Kirby Structure Accordion

> [!IMPORTANT] **This plugin is work in progress: do not use in production!** This field plugin is an UI experiment how to approach structured data in Kirby. Either for single item display (accordion) or for multiple item handling (batch editing). Be aware that the field's behaviour on multilingual sites diverges from Kirby default language handling. You might loose translated content if you use the field on existing content.

It's experimenting with UI concepts different to those Work in progress: do not use in production.

## Example blueprint

```yaml
structure:
    type: structureaccordion
    label: Structure Accordion
    help: Your help text here.
    batch: true
    sortable: true
    summary:
        label: '{{ item.tag }}'
        text: '{{ item.description.kirbytext }}'
        # empty: 'No descriptoin'
        # info: 'This is the info'
        # extra: 'This is the extra content area for longer summaries.'
    fields:
        text:
            type: text
            label: Text
        description:
            type: textarea
            label: Description
```

<img width="590" height="369" alt="grafik" src="https://github.com/user-attachments/assets/4a8997b5-df01-41a7-b152-2a7d525a1459" />

- **summary**: content used to give an overview of the item, use Kirby query language to fill those fields.
- **batch**: enabled batch editing
- **fields**: like for normal structures

> [!WARNING] In multilingual contexts, the primary language is the only one where you can make structural changes (add, delete, sort). Structural changes are synchronized to secondary languages on save. So if you delete an item in your primary language, it will also be gone in your secondary languages.

> [!WARNING] The field adds a hidden UUID to each item of the structure. This is used to synchronize structural changes across languages. If you have existing content, you might loose your translations on first save.
