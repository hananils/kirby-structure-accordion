<template>
    <rich-input
        ref="search"
        :placeholder="$t('hananils.structure-accordion.search')"
        combinators=""
        @input="onInput"
        @focus="onFocus"
        @blur="onFocus"
    >
        <datalist v-for="(options, id) in groups" :id="id" :label="label(id)">
            <option v-for="option in options" :value="option.value">
                <k-item-image
                    v-if="option.image"
                    :image="option.image"
                    width="32"
                />

                {{ option.label ?? null }}
            </option>
        </datalist>
    </rich-input>
</template>

<script>
import 'rich-input';

export default {
    inheritAttrs: false,
    props: {
        endpoints: Object,
        items: {
            type: Array,
            default: () => []
        },
        fields: Object
    },
    emits: ['input', 'search'],
    computed: {},

    data() {
        return {
            groups: {}
        };
    },

    created() {
        this.getGroups();
        this.getOptions();
    },

    mounted() {
        this.$refs.search.focus();
    },

    methods: {
        getGroups() {
            Object.keys(this.items[0]).forEach((id) => {
                if (id === 'uuid') {
                    return;
                }

                this.groups[id] = [];
            });
        },

        getOptions() {
            this.items.forEach((item) => {
                Object.keys(this.groups).forEach((id) => {
                    let value = item[id];

                    if (Array.isArray(value)) {
                        this.resolveUuids(id, value);
                    } else {
                        this.groups[id].push({ value: value });
                    }
                });
            });
        },

        async resolveUuids(id, value) {
            let type = this.fields[id].type;
            let path = 'items/pages';

            if (type.includes('user')) {
                path = 'items/users';
            } else if (type.includes('file')) {
                path = 'items/files';
            }

            let items = await this.$helper.items(path, value);

            items.forEach((item, index) => {
                if (item) {
                    this.groups[id].push({
                        value: value[index],
                        label: item.text ?? null,
                        image: item.image ?? null
                    });
                }
            });
        },

        label(id) {
            if (!this.fields[id]) {
                return;
            }

            return this.fields[id].label;
        },

        onInput(event) {
            const query = event.target.getParsedQuery();

            this.search(query);
        },

        search(query) {
            window.panel.api
                .post(this.endpoints.field + '/search', {
                    query: query
                })
                .then((response) => {
                    this.$emit('search', response.results);
                });
        }
    }
};
</script>

<style>
rich-input::part(icon) {
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='currentColor'%3E%3Cpath d='M18.031 16.6168L22.3137 20.8995L20.8995 22.3137L16.6168 18.031C15.0769 19.263 13.124 20 11 20C6.032 20 2 15.968 2 11C2 6.032 6.032 2 11 2C15.968 2 20 6.032 20 11C20 13.124 19.263 15.0769 18.031 16.6168ZM16.0247 15.8748C17.2475 14.6146 18 12.8956 18 11C18 7.1325 14.8675 4 11 4C7.1325 4 4 7.1325 4 11C4 14.8675 7.1325 18 11 18C12.8956 18 14.6146 17.2475 15.8748 16.0247L16.0247 15.8748Z'%3E%3C/path%3E%3C/svg%3E");
    mask-type: alpha;
    background-image: none;
    background-color: var(--color-text);
}

rich-input {
    margin-bottom: var(--spacing-2);
}

rich-input::part(control) {
    outline: 1px solid var(--input-color-border);
    border: none;
    border-radius: var(--input-rounded);
    background: var(--input-color-back);
    padding-inline: var(--input-padding);
    min-height: var(--input-height);
}

rich-input::part(control):focus-within {
    outline: var(--input-outline-focus);
    box-shadow: none;
    border: none;
}

rich-input::part(input) {
    --ri-placeholder-color: var(--input-color-placeholder);

    padding-block: var(--input-padding);
    color: var(--input-color-text);
    font-size: var(--input-font-size);
    line-height: var(--input-leading);
    font-family: var(--input-font-family);
}

rich-input::part(popover) {
    box-shadow: var(--dropdown-shadow);
    border: none;
    border-radius: var(--dropdown-rounded);
    background: var(--dropdown-color-bg);
    padding: var(--dropdown-padding);
    color: var(--dropdown-color-text);
    font-size: var(--text-sm);
    font-family: var(--font-sans, sans-serif);
}

rich-input::part(suggestions-header) {
    display: none;
}

rich-input::part(suggestions-list) {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 0;
}

rich-input::part(suggestion-item) {
    display: flex;
    justify-content: var(--button-align);
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    border-radius: var(--button-rounded);
    background: var(--button-color-back);
    padding-inline: var(--button-padding);
    width: var(--button-width);
    height: var(--button-height);
    color: var(--button-color-text);
    line-height: 1;
    font-variant-numeric: tabular-nums;
    user-select: none;
    text-align: var(--button-align);
    white-space: nowrap;
}

rich-input::part(suggestion-item-selected),
rich-input::part(suggestion-item-active) {
    background-color: var(--dropdown-color-hr);
}

rich-input::part(suggestion-keyword),
rich-input::part(suggestion-value) {
    overflow: visible;
    font-weight: var(--font-normal);
    font-size: var(--text-sm);
}

rich-input::part(suggestion-label) {
    overflow: visible;
    color: var(--color-text-dimmed);
    font-weight: var(--font-normal);
    font-size: var(--text-sm);
}

rich-input::part(clear-button) {
    opacity: 0.6;
    border-radius: 50%;
    background: light-dark(#000, #fff);
    padding: 0;
    width: 1rem;
    height: 1rem;
    color: light-dark(#fff, #000);
}

rich-input::part(clear-button):hover {
    opacity: 1;
}

rich-input::part(suggestion-image) {
    margin-inline-start: calc(-1 * var(--button-padding));
    border-radius: var(--button-rounded) 0 0 var(--button-rounded);
    width: 2rem;
    height: 2rem;
}

::highlight(rich-input-value) {
    background-color: light-dark(var(--color-blue-250), var(--color-blue-800));
    color: light-dark(var(--color-blue-700), var(--color-blue-300));
}

::highlight(rich-input-keyword) {
    color: light-dark(var(--color-blue-800), var(--color-blue-250));
}
</style>
