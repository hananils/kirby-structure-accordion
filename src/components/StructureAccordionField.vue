<template>
    <k-field
        v-bind="$props"
        :class="[
            'k-structure-accordion-field',
            isSelecting ? 'is-selecting' : '',
            $attrs.class
        ]"
        :input="false"
        :style="$attrs.style"
        @click.stop
        data-layout="list"
    >
        <!-- Global selection helper -->
        <template #label>
            <k-label
                v-if="label"
                :has-diff="hasDiff"
                :input="input"
                :link="link"
                :required="required"
                :title="label"
                type="field"
            >
                <span class="k-structure-accordion-select" v-if="isSelecting">
                    <input
                        type="checkbox"
                        ref="globalSelection"
                        @click="onGlobalSelect"
                    />
                </span>

                {{ label }}
            </k-label>
        </template>

        <template v-if="hasFields && !disabled" #options>
            <div class="k-structure-accordion-batch-actions" v-if="isSelecting">
                <!-- Batch editing/deleting -->
                <k-button-group
                    :buttons="optionsEditing"
                    size="xs"
                    variant="filled"
                    layout="collapsed"
                />

                <!-- Batch cancel -->
                <k-button
                    :text="$t('cancel')"
                    icon="cancel"
                    variant="filled"
                    size="xs"
                    @click="onSelectToggle"
                />
            </div>
            <k-button-group v-else layout="collapsed">
                <!-- Toggle batch editing -->
                <k-button
                    v-if="canSelect"
                    v-bind="batchEditingToggle"
                    size="xs"
                    variant="filled"
                />

                <!-- Add new item -->
                <k-button
                    v-if="canModify && canAdd"
                    :autofocus="autofocus"
                    :responsive="true"
                    :text="$t('add')"
                    icon="add"
                    variant="filled"
                    size="xs"
                    @click="onAdd"
                />

                <!-- Switch to primary language -->
                <k-button
                    v-if="isTranslation"
                    :autofocus="autofocus"
                    :responsive="true"
                    :text="$t('hananils.structure-accordion.edit')"
                    icon="translate"
                    variant="filled"
                    size="xs"
                    @click="onSwitchToPrimary"
                />

                <!-- Context menu -->
                <k-button
                    icon="dots"
                    size="xs"
                    variant="filled"
                    @click="$refs.options.toggle()"
                />
                <k-dropdown-content
                    ref="options"
                    :options="options"
                    @action="onOption"
                    align-x="end"
                />
            </k-button-group>
        </template>

        <k-validator v-bind="{ min, max, required }" :count="items.length">
            <template v-if="hasFields">
                <k-structure-accordion-search
                    :endpoints="endpoints"
                    :items="items"
                />

                <!-- Empty state -->
                <k-empty
                    v-if="items.length === 0"
                    icon="list-bullet"
                    @click="add()"
                >
                    {{ empty ?? $t('field.structure.empty') }}
                </k-empty>

                <!-- Accordion -->
                <k-draggable
                    v-else
                    element="section"
                    :list="items"
                    :handle="true"
                    @end="onSort"
                >
                    <k-structure-accordion
                        v-for="item in paginatedItems"
                        :key="item.uuid"
                        :name="name"
                        :data="item"
                        :summaries="summaries[item.uuid]"
                        :fields="$helper.field.subfields(this, fields)"
                        :expanded="expanded.includes(item.uuid)"
                        :disabled="false"
                        :selecting="isSelecting"
                        :selected="selected"
                        :sortable="isSortable"
                        :translate="translate"
                        @input="onInput"
                        @add="onAdd"
                        @insert="onInsert"
                        @duplicate="onDuplicate"
                        @remove="onRemove"
                        @toggle="onToggle"
                        @select="onSelect"
                        @deselect="onDeselect"
                    />
                </k-draggable>
            </template>
            <template v-else>
                <k-empty icon="list-bullet">{{ $t('fields.empty') }}</k-empty>
            </template>
        </k-validator>

        <template #footer>
            <footer v-if="help || isPaginated" class="k-collection-footer">
                <!-- Help text -->
                <k-text
                    v-if="help"
                    class="k-help k-collection-help"
                    :text="help"
                />

                <!-- Pagination -->
                <k-pagination
                    v-if="limit"
                    v-bind="pagination"
                    @paginate="onPaginate"
                />
            </footer>
        </template>
    </k-field>
</template>

<script>
import { props as Field } from '@/components/Forms/Field.vue';
import batchEditing from '@/mixins/batchEditing';
import 'rich-input';

export default {
    mixins: [Field, batchEditing],
    inheritAttrs: false,

    props: {
        autofocus: Boolean,

        // Content
        value: {
            type: Array,
            default: () => []
        },
        empty: String,
        data: Object,
        fields: Object,
        translate: Boolean,

        // Limits
        limit: [Number, Boolean],
        max: Number,
        min: Number,

        // Actions
        prepend: {
            type: Boolean,
            default: false
        },
        duplicate: {
            type: Boolean,
            default: true
        },

        // Sorting
        sortable: {
            type: Boolean,
            default: true
        },
        sortBy: String
    },
    emits: ['input'],
    computed: {
        /**
         * Creates field options available from the context menu.
         */
        options() {
            const options = [];

            options.push({
                icon: 'collapse',
                text: this.$t('collapse.all'),
                click: 'collapseAll'
            });
            options.push({
                icon: 'expand',
                text: this.$t('expand.all'),
                click: 'expandAll'
            });

            options.push('-');

            options.push({
                icon: 'trash',
                text: this.$t('delete.all'),
                click: 'removeAll',
                disabled: !this.canModify
            });

            return options;
        },

        /**
         * Creates batch editing buttons.
         */
        optionsEditing() {
            const buttons = [];

            buttons.push({
                text: this.$t('edit'),
                click: () => this.editSelected(),
                icon: 'edit',
                responsive: true
            });
            buttons.push({
                text: this.$t('delete'),
                click: () => this.deleteSelected(),
                icon: 'trash',
                responsive: true
            });

            return buttons;
        },

        /**
         * Checks if new items can be added based on given field limits.
         */
        canAdd() {
            // Field is disabled
            if (this.disabled === true) {
                return false;
            }

            // Field has reached is limits
            if (this.max && this.items.length >= this.max) {
                return false;
            }

            return true;
        },

        /**
         * Checks if structural modifications are allowed. Additive and
         * destructive action are limited to the primary language. Secondary
         * languages are only allowed to work with existing content, focussing
         * on translations.
         */
        canModify() {
            return panel.language.isDefault;
        },

        /**
         * Returns true if the user is currently editing a secondary languages.
         */
        isTranslation() {
            return !panel.language.isDefault;
        },

        /**
         * Returns true if the the field can be sorted manually.
         */
        isSortable() {
            // Field has sorting actively disabled in the blueprint
            if (this.sortable === false) {
                return false;
            }

            // Field is automatically sorted
            if (this.sortBy) {
                return false;
            }

            // Field is paginated:
            // This is a general Kirby convention because there is no UI to
            // cross page boundaries. Nevertheless, there is no reason
            // why you shouldn't be able to sort on the current page.
            // This needs discussion.
            if (this.isPaginated) {
                return false;
            }

            // Field is disabled
            if (this.disabled === true) {
                return false;
            }

            // Field is empty
            if (this.items.length <= 1) {
                return false;
            }

            // Field structure cannot be modified
            if (!this.canModify) {
                return false;
            }

            // Field is batch edited
            if (this.isSelecting) {
                return false;
            }

            // Alright, go on: sort
            return true;
        },

        /**
         * Returns true if the field is paginated as required.
         */
        isPaginated() {
            return this.limit !== false;
        },

        /**
         * Returns false if no subfields have been defined yet-
         */
        hasFields() {
            return this.$helper.object.length(this.fields) > 0;
        },

        /**
         * Returns the items of the current page if field is paginated.
         * Returns all items otherwise.
         */
        paginatedItems() {
            if (this.isPaginated) {
                return this.items.slice(
                    this.pagination.offset,
                    this.pagination.offset + this.limit
                );
            }

            return this.items;
        },

        /**
         * Returns the pagination configuration
         */
        pagination() {
            let offset = 0;

            if (this.limit) {
                offset = (this.page - 1) * this.limit;
            }

            return {
                page: this.page,
                offset: offset,
                limit: this.limit,
                total: this.items.length,
                align: 'center',
                details: true
            };
        }
    },

    data() {
        return {
            items: this.value,
            summaries: this.data,
            expanded: [],
            page: 1
        };
    },

    watch: {
        value: {
            handler(value) {
                // Value has changed, update field
                if (value !== this.items) {
                    this.getItems(value);
                    this.getSummaries(value);
                }
            },
            immediate: true
        }
    },

    mounted() {
        this.$events.on('content.publish', this.publish);
    },

    destroyed() {
        this.$events.off('content.publish', this.publish);
    },

    methods: {
        /**
         * Gets all items.
         */
        getItems(value) {
            this.items = value ?? [];
        },

        /**
         * Gets all summaries indexed by their UUID.
         * This never determines the sort order, use items for that.
         */
        getSummaries(items) {
            window.panel.api
                .post(this.endpoints.field + '/summarize', {
                    items: items
                })
                .then((response) => {
                    this.summaries = response.summaries;
                });
        },

        /**
         * Returns the index of the item given its UUID.
         */
        getIndex(uuid) {
            return this.items.findIndex((item) => {
                return item.uuid === uuid;
            });
        },

        /**
         * Creates an empty item.
         */
        createBlank() {
            let uuid = this.$helper.uuid();

            this.expanded.push(uuid);

            return {
                uuid: uuid
            };
        },

        /**
         * Expands all items.
         */
        expandAll() {
            this.expanded = this.items.map((item) => item.uuid);
        },

        /**
         * Collapses all items.
         */
        collapseAll() {
            this.expanded = [];
        },

        /**
         * Removes all items.
         */
        removeAll() {
            this.$panel.dialog.open({
                component: 'k-remove-dialog',
                props: {
                    text: this.$t('field.structure.delete.confirm.all')
                },
                on: {
                    submit: () => {
                        this.page = 1;
                        this.items = [];
                        this.summaries = [];
                        this.save();
                        this.$panel.dialog.close();
                    }
                }
            });
        },

        /**
         * Selects all items.
         */
        selectAll() {
            this.selected = this.items.map((item) => item.uuid);
            this.reflectSelection();
        },

        /**
         * Deselects all items.
         */
        deselectAll() {
            this.selected = [];
            this.reflectSelection();
        },

        /**
         * Reflects the selection state (all, some, none) in the global
         * selection helper
         */
        reflectSelection() {
            const checkbox = this.$refs.globalSelection;
            const max = this.limit ? this.limit : this.items.length;
            const count = this.selected.length;

            if (count === max) {
                checkbox.checked = true;
                checkbox.indeterminate = false;
            } else if (count === 0) {
                checkbox.checked = false;
                checkbox.indeterminate = false;
            } else {
                checkbox.checked = true;
                checkbox.indeterminate = true;
            }
        },

        /**
         * Selects or deselects all items depending on the previous selection state.
         */
        onGlobalSelect() {
            const checkbox = this.$refs.globalSelection;
            const max = this.limit ? this.limit : this.items.length;
            const count = this.selected.length;

            if (count === max) {
                this.deselectAll();
            } else {
                this.selectAll();
            }
        },

        /**
         * Batch edits selected items.
         */
        editSelected() {
            this.$panel.dialog.open({
                component: 'k-structure-accordion-edit',
                props: {
                    fields: this.fields,
                    items: this.selected,
                    submitButton: this.$t(
                        'hananils.structure-accordion.batch.submit'
                    )
                },
                on: {
                    submit: async (data) => {
                        let edits = await this.$api.post(
                            this.endpoints.field + '/replace',
                            {
                                selected: this.items.filter((item) =>
                                    this.selected.includes(item.uuid)
                                ),
                                fields: data
                            }
                        );

                        Object.keys(data).forEach((key) => {
                            this.items.forEach((item, index) => {
                                if (!this.selected.includes(item.uuid)) {
                                    return;
                                }

                                this.items[index] = edits.items.find(
                                    (edit) => edit.uuid === item.uuid
                                );
                            });
                        });

                        this.getSummaries(this.items);

                        this.$panel.dialog.close();

                        if (this.selected.length === 0) {
                            return;
                        }

                        this.stopSelecting();
                    }
                }
            });
        },

        /**
         * Batch deletes selected items.
         */
        deleteSelected() {
            this.$panel.dialog.open({
                component: 'k-remove-dialog',
                props: {
                    text: this.$t('field.structure.delete.confirm.selected')
                },
                on: {
                    submit: async () => {
                        this.$panel.dialog.close();

                        if (this.selected.length === 0) {
                            this.stopSelecting();
                            return;
                        }

                        await this.onBatchDelete();
                        this.stopSelecting();
                    }
                }
            });
        },

        /**
         * Inserts an given item if given or creates a new one otherwise.
         */
        insert(index = 0, direction = 'before', item = null) {
            if (!item) {
                item = this.createBlank();
            }

            if (direction === 'after') {
                this.items.splice(index + 1, 0, item);
            } else {
                this.items.splice(index, 0, item);
            }
        },

        /**
         * Saves a version of the field content.
         * Be aware: this is called on every change, see publish for final save.
         */
        save() {
            this.$emit('input', this.items);
        },

        /**
         * Synchronizes languages when a version got published.
         */
        publish() {
            window.panel.api.get(this.endpoints.field + '/synchronize');
        },

        /**
         * Input action.
         */
        onInput(data) {
            let index = this.getIndex(data.uuid);

            this.items[index] = data;
            this.save();
        },

        /**
         * Duplicate action.
         */
        onDuplicate(uuid) {
            let index = this.getIndex(uuid);
            let duplicate = this.$helper.object.clone(this.items[index]);

            duplicate.uuid = this.$helper.uuid();

            this.summaries[duplicate.uuid] = this.$helper.object.clone(
                this.summaries[uuid]
            );

            this.insert(index, 'after', duplicate);
            this.save();
        },

        /**
         * Add action.
         */
        onAdd() {
            let item = this.createBlank();

            if (this.prepend === true) {
                // Prepend new item and go to first page
                this.items.unshift(item);
                this.page = 1;
            } else {
                // Append new item and go to last page
                this.items.push(item);
                this.page = Math.ceil(
                    this.pagination.total / this.pagination.limit
                );
            }

            this.save();
        },

        /**
         * Remove action.
         */
        onRemove(uuid) {
            let index = this.getIndex(uuid);

            this.items.splice(index, 1);
            this.save();
        },

        /**
         * Toggle interaction.
         */
        onToggle(uuid) {
            if (this.expanded.includes(uuid)) {
                this.expanded = this.expanded.filter(
                    (expanded) => expanded !== uuid
                );
            } else {
                this.expanded.push(uuid);
            }
        },

        /**
         * Option interaction.
         */
        onOption(event, row, rowIndex) {
            switch (event) {
                case 'expandAll':
                    this.expandAll();
                    break;
                case 'collapseAll':
                    this.collapseAll();
                    break;
                case 'removeAll':
                    this.removeAll();
                    break;
            }
        },

        /**
         * Insert action.
         */
        onInsert(direction, uuid) {
            let index = this.getIndex(uuid);

            this.insert(index, direction);
            this.save();
        },

        /**
         * Sort action.
         */
        onSort() {
            this.save();
        },

        /**
         * Paginate action.
         */
        onPaginate({ page }) {
            this.page = page;
        },

        /**
         * Action to switch to primary language.
         */
        async onSwitchToPrimary() {
            try {
                // abort the switch when the lock could not be released,
                // because the pending changes could not be written first.
                // `unlock` already reports those cases, so staying on the
                // current language is all that is left to do here
                if ((await this.$panel.content.unlock()) !== true) {
                    return;
                }
            } catch (error) {
                this.$panel.error(error);
                return;
            }

            this.$reload({
                query: {
                    language: panel.languages.find(
                        (language) => language.default === true
                    ).code
                }
            });
        },

        /**
         * Select action.
         */
        onSelect(uuid) {
            this.selected.push(uuid);
            this.reflectSelection();
        },

        /**
         * Deselect action.
         */
        onDeselect(uuid) {
            this.selected = this.selected.filter(
                (selected) => selected !== uuid
            );
            this.reflectSelection();
        },

        /**
         * Batch delete action.
         */
        onBatchDelete() {
            this.items = this.items.filter(
                (item) => this.selected.includes(item.uuid) === false
            );
            this.save();
        }
    }
};
</script>

<style>
.k-structure-accordion-field {
    section {
        display: flex;
        flex-direction: column;

        /* Be aware that the core still uses margins to push items apart */
        gap: 2px;
    }

    footer {
        /* Compensate for the gap of the section above */
        margin-top: calc(var(--spacing-2) - 2px);
    }

    .k-structure-accordion-batch-actions {
        display: flex;
        gap: var(--spacing-2);
    }

    @media (width <= 750px) {
        &.is-selecting {
            padding-inline-start: 1.5rem;
        }
    }
}

/**
 * Not sure it this is the best solution, but it needs some visual separation
 * between the global and the single select buttons.
 */
.k-label-text .k-structure-accordion-select:after {
    position: absolute;
    top: 1rem;
    right: 1px;
    left: 1px;
    border-bottom: 1px solid
        light-dark(rgb(0 0 0 / 0.15), rgb(255 255 255 / 0.25));
    height: 0.7rem;
    content: '';
}
</style>
