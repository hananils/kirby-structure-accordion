<template>
    <rich-input placeholder="Search …">
        <datalist v-for="(values, key) in keys" :id="key">
            <option v-for="value in values" :value="value"></option>
        </datalist>
    </rich-input>
</template>

<script>
import 'rich-input';

export default {
    inheritAttrs: false,
    props: {
        items: {
            type: Array,
            default: () => []
        }
    },
    emits: ['input'],
    computed: {
        keys() {
            let keys = {};

            if (!this.items.length) {
                return keys;
            }

            Object.keys(this.items[0]).forEach((key) => {
                keys[key] = [];
            });

            this.items.forEach((item) => {
                Object.keys(item).forEach((key) => {
                    keys[key].push(item[key]);
                });
            });

            return keys;
        }
    },

    data() {},

    watch: {},

    methods: {}
};
</script>

<style>
rich-input::part(icon) {
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='currentColor'%3E%3Cpath d='M18.031 16.6168L22.3137 20.8995L20.8995 22.3137L16.6168 18.031C15.0769 19.263 13.124 20 11 20C6.032 20 2 15.968 2 11C2 6.032 6.032 2 11 2C15.968 2 20 6.032 20 11C20 13.124 19.263 15.0769 18.031 16.6168ZM16.0247 15.8748C17.2475 14.6146 18 12.8956 18 11C18 7.1325 14.8675 4 11 4C7.1325 4 4 7.1325 4 11C4 14.8675 7.1325 18 11 18C12.8956 18 14.6146 17.2475 15.8748 16.0247L16.0247 15.8748Z'%3E%3C/path%3E%3C/svg%3E")
        no-repeat center / contain;
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
    border: none;
}

rich-input::part(input) {
    padding-block: var(--input-padding);
    color: var(--input-color-text);
    font-size: var(--input-font-size);
    line-height: var(--input-leading);
    font-family: var(--input-font-family);
}

/* Style artist names */
::highlight(rich-input-value) {
    background-color: var(--color-blue-250);
    color: var(--color-blue-700);
}

/* Generic prefix highlight for keyword labels (e.g. "label:", "year:") */
::highlight(rich-input-keyword) {
    color: var(--color-blue-800);
}

/* Invalid highlight (squiggly underline for unrecognized keywords or values not present in datalist) */
::highlight(rich-input-invalid) {
    text-decoration: underline wavy #ef4444;
    text-decoration-skip-ink: none;
}
</style>
