<template>
    <k-dialog
        ref="dialog"
        v-bind="$props"
        @cancel="$emit('cancel')"
        @submit="onSubmit"
    >
        <slot>
            <h2>{{ $t('hananils.structure-accordion.batch.title') }}</h2>
            <p>{{ $t('hananils.structure-accordion.batch.info') }}</p>

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
.k-dialog-body > h2 {
    font-weight: bold;
    line-height: 1.5;
}

.k-dialog-body > p {
    margin-block-end: var(--spacing-6);
    line-height: 1.5;
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
