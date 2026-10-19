import StructureAccordionField from './components/StructureAccordionField.vue';
import StructureAccordion from './components/StructureAccordion.vue';
import StructureAccordionEdit from './components/StructureAccordionEdit.vue';

panel.plugin('hananils/structure-accordion', {
    components: {
        'k-structure-accordion': StructureAccordion,
        'k-structure-accordion-edit': StructureAccordionEdit
    },
    fields: {
        structureaccordion: StructureAccordionField
    }
});
