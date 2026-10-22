<template>
    <k-dialog
        ref="dialog"
        v-bind="$props"
        @cancel="$emit('cancel')"
        @submit="onSubmit"
    >
        <slot>
            <header>
                <h2>
                    {{ $t('hananils.structure-accordion.batch.title') }}
                </h2>
                <details>
                    <summary>
                        <k-icon type="magic" />
                        {{ $t('hananils.structure-accordion.batch.help') }}
                    </summary>

                    <p>
                        {{
                            $t(
                                'hananils.structure-accordion.batch.placeholders'
                            )
                        }}
                    </p>
                    <ul>
                        <li
                            v-html="
                                $t(
                                    'hananils.structure-accordion.batch.placeholders.field'
                                )
                            "
                        />
                        <li
                            v-html="
                                $t(
                                    'hananils.structure-accordion.batch.placeholders.item'
                                )
                            "
                        />
                        <li
                            v-html="
                                $t(
                                    'hananils.structure-accordion.batch.placeholders.index'
                                )
                            "
                        />
                        <li
                            v-html="
                                $t(
                                    'hananils.structure-accordion.batch.placeholders.position'
                                )
                            "
                        />
                    </ul>
                </details>
                <p>{{ $t('hananils.structure-accordion.batch.info') }}</p>
            </header>

            <!--
                This layout is more a hack currently:

                Ideally, the checkboxes would be part of the dialog form itself.
                But this would require a lot of duplication of core components.
                This is a intermediate solution to test if the is a concept
                worth pursuing.

                Be aware that the current solution can be confusing because the
                field title is the label for the input (thus focussing on that
                in click) while it looks like the label for the checkbox.
                Not good.
            -->
            <div class="k-structure-accordion-edit" :style="count">
                <!-- Checkboxes for each field -->
                <div
                    class="k-structure-accordion-edit-select"
                    v-for="(field, key, index) in fields"
                    :style="`--row: ${index + 1}`"
                >
                    <input
                        type="checkbox"
                        :checked="
                            Object.keys(this.selected).includes(field.name)
                        "
                        :value="field.name"
                        @click="onCheck"
                    />
                </div>

                <!-- The actual fields -->
                <k-dialog-fields
                    :fields="fields"
                    :value="value"
                    @input="onInput"
                    @submit="onSubmit"
                />
            </div>
        </slot>
    </k-dialog>
</template>

<script>
import { toValue } from 'vue';
import Dialog from '@/mixins/dialog.js';
import { props as FieldsProps } from '@/components/Dialogs/Elements/Fields.vue';

/**
 * @copyright Bastian Allgeier
 * @license   https://getkirby.com/license
 */
export default {
    mixins: [Dialog, FieldsProps],
    props: {
        size: {
            default: 'medium'
        },
        submitButton: {
            default: () => window.panel.t('save')
        },
        text: {
            type: String
        }
    },

    computed: {
        count() {
            return '--rows: ' + (Object.values(this.fields).length + 1);
        }
    },
    emits: ['cancel', 'input', 'submit'],

    data() {
        return { selected: {}, data: {} };
    },

    methods: {
        /**
         * Select action.
         */
        onCheck({ target }) {
            if (target.checked === true) {
                this.selected[target.value] = this.data[target.value] ?? null;
            } else {
                delete this.selected[target.value];
            }
        },

        /**
         * Input action.
         */
        onInput(data) {
            Object.keys(data).forEach((key) => {
                this.data[key] = data[key];
                this.selected[key] = data[key];
            });
        },

        /**
         * Submit action.
         */
        onSubmit(event) {
            this.$emit('submit', this.selected);
        }
    }
};
</script>

<style>
.k-dialog-body > header {
    --negative-dialog-padding: calc(-1 * var(--dialog-padding));

    display: grid;
    grid-template-columns: 1fr;
    align-items: start;
    margin-inline: var(--negative-dialog-padding);
    margin-block: var(--negative-dialog-padding) var(--dialog-padding);
    border-bottom: 1px solid var(--input-color-border);
    background: var(--item-color-back);
    padding: var(--spacing-4) var(--dialog-padding) var(--spacing-4);
    line-height: 1.5;

    h2 {
        display: flex;
        grid-row: 1 / 2;
        grid-column: 1 / -1;
        justify-content: space-between;
        align-items: last baseline;
        margin-bottom: var(--spacing-2);
        font-weight: var(--font-semi);
        font-size: var(--text-lg);
    }

    details {
        display: flex;
        grid-row: 1 / 2;
        grid-column: 1 / -1;
        flex-direction: column;

        &[open] summary {
            margin-bottom: var(--spacing-2);
        }
    }

    summary {
        display: inline-flex;
        position: relative;
        flex-shrink: 0;
        align-items: center;
        align-self: flex-end;
        gap: var(--spacing-2);
        margin-top: 0.175rem;
        border-radius: var(--button-rounded);
        background-color: var(--panel-color-back);
        padding-inline: var(--button-padding);
        height: var(--height-xs);
        color: var(--button-color-text);
        line-height: 1;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    ul {
        list-style: none;

        li {
            margin-block-start: var(--spacing-3);
            padding-left: 1rem;

            &::before {
                display: inline-block;
                margin-left: -1rem;
                margin: 0.2rem 0.6rem 0.2rem -1rem;
                border-radius: 50%;
                background: var(--button-color-text);
                width: 0.25rem;
                height: 0.25rem;
                content: '';
            }

            code {
                display: inline-block;
                border-radius: var(--button-rounded);
                background-color: var(--panel-color-back);
                padding-inline: var(--spacing-1);
                color: var(--button-color-text);
                font-size: 0.9em;
            }
        }
    }

    details[open] + p {
        margin-block-start: var(--spacing-3);
    }
}

.k-structure-accordion-edit {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--spacing-6) 0.5rem;
    margin-bottom: 0.2rem;

    .k-structure-accordion-edit-select {
        grid-row-start: var(--row, 1);
        grid-column: 1 / -1;
        align-self: start;
        justify-self: start;
        z-index: 100;
        margin-top: 0.3rem;
    }

    .k-dialog-fields {
        display: contents;

        .k-grid {
            display: grid;
            grid-template-rows: subgrid;
            grid-row-start: 1;
            grid-row-end: var(--rows);
            grid-column: 1 / -1;
            gap: var(--spacing-6);
        }

        .k-label-text {
            margin-left: 1.5rem;
        }
    }
}
</style>
