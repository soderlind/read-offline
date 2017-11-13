<?php 
/**
	Admin Page Framework v3.8.15 by Michael Uno 
	Generated by PHP Class Files Script Generator <https://github.com/michaeluno/PHP-Class-Files-Script-Generator>
	<http://en.michaeluno.jp/read-offline>
	Copyright (c) 2013-2017, Michael Uno; Licensed under MIT <http://opensource.org/licenses/MIT> */
class Read_Offline_Settings_AdminPageFramework_Form_View___Attribute_SectionTableBody extends Read_Offline_Settings_AdminPageFramework_Form_View___Attribute_Base {
    public $sContext = 'section_table_content';
    protected function _getAttributes() {
        $_sCollapsibleType = $this->getElement($this->aArguments, array('collapsible', 'type'), 'box');
        return array('class' => $this->getAOrB($this->aArguments['_is_collapsible'], 'read-offline-collapsible-section-content' . ' ' . 'read-offline-collapsible-content' . ' ' . 'accordion-section-content' . ' ' . 'read-offline-collapsible-content-type-' . $_sCollapsibleType, null),);
    }
}
