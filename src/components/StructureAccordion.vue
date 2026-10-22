<template>
    <article class="k-item k-structure-accordion">
        <div class="k-item-box">
            <div class="k-structure-accordion-summary">
                <!-- Select checkbox -->
                <template v-if="selecting">
                    <label class="k-structure-accordion-select">
                        <input
                            type="checkbox"
                            :checked="selected.includes(data.uuid)"
                            @click="onCheck"
                        />
                    </label>
                </template>

                <!-- Sort handle -->
                <k-sort-handle
                    v-if="sortable"
                    class="k-item-sort-handle"
                    tabindex="-1"
                />

                <!-- Content toggle -->
                <button
                    class="k-structure-accordion-toggle"
                    type="button"
                    :aria-label="$t('hananils.structure-accordion.toggle')"
                    :aria-expanded="expanded === true ? 'true' : 'false'"
                    :aria-controls="id"
                    @click="onToggle"
                >
                    <k-icon type="angle-right" />
                </button>

                <!-- Summary -->
                <header>
                    <small v-if="summaries.label">{{ summaries.label }}</small>
                    <h2 v-if="summaries.text">{{ summaries.text }}</h2>
                    <h2 v-else>{{ $t('hananils.structure-accordion.new') }}</h2>
                    <span v-if="summaries.info">{{ summaries.info }}</span>
                </header>
                <p v-if="summaries.extra">{{ summaries.extra }}</p>

                <!-- Options -->
                <k-options-dropdown
                    v-if="canModify && !selecting"
                    :options="options"
                    :text="options.length > 1"
                    @action="onOption"
                />
            </div>
            <div
                class="k-structure-accordion-content"
                :hidden="!expanded"
                :inert="!translate"
            >
                <!-- Content form -->
                <k-form
                    ref="form"
                    :autofocus="true"
                    :disabled="disabled || selecting"
                    :fields="fields"
                    :value="data"
                    :id="id"
                    @input="onInput"
                />
            </div>
        </div>
    </article>
</template>

<script>
import debounce from '@/helpers/debounce.js';

export default {
    inheritAttrs: false,
    props: {
        // content
        name: String,
        fields: Object,
        data: Object,
        summaries: {
            type: Object,
            default: {
                label: null,
                text: null,
                info: null,
                extra: null
            }
        },
        translate: Boolean,

        // Toggling
        expanded: Boolean,
        disabled: Boolean,

        // Sorting
        sortable: Boolean,

        // Selecting
        selecting: Boolean,
        selected: {
            type: Array,
            default: []
        }
    },

    computed: {
        /**
         * Checks if the current page is a translation.
         */
        isTranslation() {
            return !panel.language.isDefault;
        },

        /**
         * Checks if the current page can modify the structure.
         */
        canModify() {
            if (this.isTranslation) {
                return false;
            }

            return true;
        },

        /**
         * Returns the item id.
         */
        id() {
            return this.name + '-' + this.data.uuid;
        },

        /**
         * Check if item has only one data entry with value and if this entry is
         * the UUID.
         */
        isBlank() {
            return (
                Object.values(this.data).filter((value) => value).length ===
                    1 && this.data.hasOwnProperty('uuid')
            );
        },

        /**
         * Creates field options available from the context menu.
         */
        options() {
            if (this.disabled) {
                return [];
            }

            let options = [];
            options.push({
                icon: 'angle-up',
                text: this.$t('insert.before'),
                click: 'before'
            });
            options.push({
                icon: 'angle-down',
                text: this.$t('insert.after'),
                click: 'after'
            });

            options.push('-');

            options.push({
                icon: 'copy',
                text: this.$t('duplicate'),
                click: 'duplicate'
            });

            options.push('-');

            options.push({
                icon: 'trash',
                text: this.$t('delete'),
                click: 'remove'
            });

            return options;
        }
    },

    methods: {
        /**
         * Input action.
         */
        onInput(data) {
            this.$emit('input', data);
        },

        /**
         * Toggle action.
         */
        onToggle({ target }) {
            this.$emit('toggle', this.data.uuid);
        },

        /**
         * Select action.
         */
        onCheck({ target }) {
            if (target.checked === true) {
                this.$emit('select', this.data.uuid);
            } else {
                this.$emit('deselect', this.data.uuid);
            }
        },

        /**
         * Context action.
         */
        onOption(event, row, rowIndex) {
            switch (event) {
                case 'before':
                case 'after':
                    this.$emit('insert', event, this.data.uuid);
                    break;
                case 'duplicate':
                    this.$emit('duplicate', this.data.uuid);
                    break;
                case 'remove':
                    this.$emit('remove', this.data.uuid);
                    break;
            }
        }
    }
};
</script>

<style>
.k-structure-accordion {
    --color-background-summary: var(--item-color-back);
    --color-background-details: light-dark(
        var(--color-gray-150),
        var(--color-gray-800)
    );
    --color-separator: light-dark(var(--color-gray-300), var(--color-gray-900));
    --color-border: light-dark(var(--color-gray-300), var(--color-gray-900));
    --input-color-border: light-dark(
        var(--color-gray-300),
        var(--color-gray-750)
    );
    --input-color-back-disabled: light-dark(
        var(--panel-color-back),
        color-mix(var(--color-gray-700), var(--color-gray-850))
    );

    .k-item-box {
        display: grid;
        grid-template-rows: min-content 0fr;
        background: var(--color-background-summary);
        min-height: var(--field-input-height);
    }

    &:has(.k-structure-accordion-select input:checked) .k-item-box {
        --color-background-summary: light-dark(
            var(--color-blue-700),
            var(--color-blue-850)
        );
        --color-background-details: light-dark(
            var(--color-blue-650),
            var(--color-blue-800)
        );
        --color-text: #fff;
        --color-text-dimmed: rgb(255 255 255 / 0.8);
        --color-separator: light-dark(
            var(--color-blue-750),
            var(--color-blue-900)
        );
        --color-border: light-dark(
            var(--color-blue-700),
            var(--color-blue-750)
        );
        --input-color-back: var(--color-background-summary);
        --input-color-border: var(--color-border);
        --input-color-back-disabled: light-dark(
            color-mix(var(--color-blue-600), var(--color-blue-650)),
            color-mix(var(--color-blue-700), var(--color-blue-850))
        );

        box-shadow:
            var(--item-shadow),
            inset 0 0 1px var(--color-blue-650);

        [data-theme],
        .k-icon {
            color: rgb(255 255 255 / 0.8) !important;
        }

        [data-theme='empty'] {
            --theme-color-back: var(--input-color-back-disabled);
            --theme-color-border: var(--color-border);
            --theme-color-icon: var(--color-blue-800);
            --theme-color-text-highlight: var(--color-text-dimmed);
        }
    }
}

.k-structure-accordion-field[data-disabled='true'] {
    cursor: initial;

    * {
        pointer-events: all;
    }

    .k-structure-accordion {
        opacity: 0.6;
    }
}

/* Selecting */

.k-structure-accordion-select {
    --button-width: calc(1.3rem + var(--spacing-1));

    position: absolute;
    left: calc(-1 * var(--button-width));
    grid-row-start: 1;
    grid-column-start: 1;
    align-self: center;
}

/* Sorting */

.k-structure-accordion .k-sort-handle {
    --button-width: calc(1.5rem + var(--spacing-1));
    --button-height: var(--field-input-height);

    left: calc(-1 * var(--button-width));
    grid-row-start: 1;
    grid-column-start: 1;
    align-self: center;
}

/* Toggling */

.k-structure-accordion-toggle {
    display: flex;
    grid-row-start: 1;
    grid-column-start: 1;
    align-self: center;
    appearance: none;
    padding: 0.4rem 0.6rem 0.5rem;
    color: var(--color-text);
    line-height: 1.5;

    svg {
        transition: transform 0.15s ease-in-out;
        margin-top: 0.15rem;
    }

    &[aria-expanded='true'] svg {
        transform: rotate(90deg);
    }
}

.k-structure-accordion:has([aria-expanded='true']) {
    .k-item-box {
        grid-template-rows: min-content 1fr;
    }

    .k-structure-accordion-content {
        grid-template-rows: 1fr;
    }
}

/* Summary */

.k-structure-accordion-summary {
    display: grid;
    grid-template-columns: 34px 1fr min-content;

    header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        padding-block: 0.4rem 0.5rem;
        color: var(--color-text);
        line-height: var(--text-line-height);

        span,
        small {
            color: var(--color-text-dimmed);
        }

        small {
            flex-basis: 100%;
            font-size: var(--text-xs);
            line-height: var(--text-line-height);
        }

        /* Switch to grid when label and info are present */
        &:has(span),
        &:has(small) {
            display: grid;
            grid-template-columns: auto auto;

            small {
                grid-row-start: 1;
            }

            h2 {
                grid-row-start: 2;
            }

            span {
                grid-row: 1 / 3;
                align-self: center;
            }
        }

        /* Highlight text if extra content is present */
        &:has(+ p) h2 {
            font-weight: var(--font-semi);
        }
    }

    p {
        grid-row-start: 2;
        grid-column-start: 2;
        margin-block: 0 0.5rem;
        line-height: var(--text-line-height);
    }

    header {
        display: grid;
        grid-column: 2 / -2;
    }
}

/* Content */

.k-structure-accordion-content {
    border-radius: 0 0 var(--rounded) var(--rounded);
    background-color: var(--color-background-details);
    overflow: hidden;
    color: var(--color-text);

    .k-form {
        border-top: 1px solid var(--color-separator);
        padding: 0.5rem 0.8rem 1.3rem;
    }

    .k-grid {
        gap: var(--spacing-6);
    }

    .k-input[data-disabled='true'] {
        background-color: var(--input-color-back-disabled);
    }
}
</style>
