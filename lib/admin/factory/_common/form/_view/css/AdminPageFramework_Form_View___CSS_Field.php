<?php 
/**
	Admin Page Framework v3.8.26 by Michael Uno 
	Generated by PHP Class Files Script Generator <https://github.com/michaeluno/PHP-Class-Files-Script-Generator>
	<http://en.michaeluno.jp/read-offline>
	Copyright (c) 2013-2021, Michael Uno; Licensed under MIT <http://opensource.org/licenses/MIT> */
class Read_Offline_AdminPageFramework_Form_View___CSS_Field extends Read_Offline_AdminPageFramework_Form_View___CSS_Base {
    protected function _get() {
        return $this->___getFormFieldRules();
    }
    static private function ___getFormFieldRules() {
        return "td.read-offline-field-td-no-title {padding-left: 0;padding-right: 0;}.read-offline-fields {display: table; width: 100%;table-layout: fixed;}.read-offline-field input[type='number'] {text-align: right;} .read-offline-fields .disabled,.read-offline-fields .disabled input,.read-offline-fields .disabled textarea,.read-offline-fields .disabled select,.read-offline-fields .disabled option {color: #BBB;}.read-offline-fields hr {border: 0; height: 0;border-top: 1px solid #dfdfdf; }.read-offline-fields .delimiter {display: inline;}.read-offline-fields-description {margin-bottom: 0;}.read-offline-field {float: left;clear: both;display: inline-block;margin: 1px 0;}.read-offline-field label {display: inline-block; width: 100%;}@media screen and (max-width: 782px) {.form-table fieldset > label {display: inline-block;}}.read-offline-field .read-offline-input-label-container {margin-bottom: 0.25em;}@media only screen and ( max-width: 780px ) { .read-offline-field .read-offline-input-label-container {margin-top: 0.5em; margin-bottom: 0.5em;}} .read-offline-field .read-offline-input-label-string {padding-right: 1em; vertical-align: middle; display: inline-block; }.read-offline-field .read-offline-input-button-container {padding-right: 1em; }.read-offline-field .read-offline-input-container {display: inline-block;vertical-align: middle;}.read-offline-field-image .read-offline-input-label-container { vertical-align: middle;}.read-offline-field .read-offline-input-label-container {display: inline-block; vertical-align: middle; } .repeatable .read-offline-field {clear: both;display: block;}.read-offline-repeatable-field-buttons {float: right; margin: 0.1em 0 0.5em 0.3em;vertical-align: middle;}.read-offline-repeatable-field-buttons .repeatable-field-button {margin: 0 0.1em;font-weight: normal;vertical-align: middle;text-align: center;}@media only screen and (max-width: 960px) {.read-offline-repeatable-field-buttons {margin-top: 0;}}.read-offline-sections.sortable-section > .read-offline-section,.sortable > .read-offline-field {clear: both;float: left;display: inline-block;padding: 1em 1.32em 1em;margin: 1px 0 0 0;border-top-width: 1px;border-bottom-width: 1px;border-bottom-style: solid;-webkit-user-select: none;-moz-user-select: none;user-select: none; text-shadow: #fff 0 1px 0;-webkit-box-shadow: 0 1px 0 #fff;box-shadow: 0 1px 0 #fff;-webkit-box-shadow: inset 0 1px 0 #fff;box-shadow: inset 0 1px 0 #fff;-webkit-border-radius: 3px;border-radius: 3px;background: #f1f1f1;background-image: -webkit-gradient(linear, left bottom, left top, from(#ececec), to(#f9f9f9));background-image: -webkit-linear-gradient(bottom, #ececec, #f9f9f9);background-image: -moz-linear-gradient(bottom, #ececec, #f9f9f9);background-image: -o-linear-gradient(bottom, #ececec, #f9f9f9);background-image: linear-gradient(to top, #ececec, #f9f9f9);border: 1px solid #CCC;background: #F6F6F6;} .read-offline-fields.sortable {margin-bottom: 1.2em; } .read-offline-field .button.button-small {width: auto;} .font-lighter {font-weight: lighter;} .read-offline-field .button.button-small.dashicons {font-size: 1.2em;padding-left: 0.2em;padding-right: 0.22em;min-width: 1em; }@media screen and (max-width: 782px) {.read-offline-field .button.button-small.dashicons {min-width: 1.8em; }}.read-offline-field .button.button-small.dashicons:before {position: relative;top: 7.2%;}@media screen and (max-width: 782px) {.read-offline-field .button.button-small.dashicons:before {top: 8.2%;}}.read-offline-field-title {font-weight: 600;min-width: 80px;margin-right: 1em;}.read-offline-fieldset {font-weight: normal;}.read-offline-input-label-container,.read-offline-input-label-string{min-width: 140px;}";
    }
    protected function _getVersionSpecific() {
        $_sCSSRules = '';
        if (version_compare($GLOBALS['wp_version'], '3.8', '<')) {
            $_sCSSRules.= ".read-offline-field .remove_value.button.button-small {line-height: 1.5em; }";
        }
        $_sCSSRules.= $this->___getForWP38OrAbove();
        $_sCSSRules.= $this->___getForWP53OrAbove();
        return $_sCSSRules;
    }
    private function ___getForWP38OrAbove() {
        if (version_compare($GLOBALS['wp_version'], '3.8', '<')) {
            return '';
        }
        return ".read-offline-repeatable-field-buttons {margin: 2px 0 0 0.3em;}.read-offline-repeatable-field-buttons.disabled > .repeatable-field-button {color: #edd;border-color: #edd;} @media screen and ( max-width: 782px ) {.read-offline-fieldset {overflow-x: hidden;overflow-y: hidden;}}";
    }
    private function ___getForWP53OrAbove() {
        if (version_compare($GLOBALS['wp_version'], '5.3', '<')) {
            return '';
        }
        return ".read-offline-field .button.button-small.dashicons:before {position: relative;top: -5.4px;}@media screen and (max-width: 782px) {.read-offline-field .button.button-small.dashicons:before {top: -6.2%;}.read-offline-field .button.button-small.dashicons {min-width: 2.4em;}}.read-offline-repeatable-field-buttons .repeatable-field-button.button.button-small {min-width: 2.4em;padding: 0;}.repeatable-field-button .dashicons {position: relative;top: 4.4px;font-size: 16px;}@media screen and (max-width: 782px) {.read-offline-repeatable-field-buttons {margin: 0.5em 0 0 0.28em;}.repeatable-field-button .dashicons {position: relative;top: 10px;font-size: 18px;}.read-offline-repeatable-field-buttons .repeatable-field-button.button.button-small {margin-top: 0;margin-bottom: 0;min-width: 2.6em;min-height: 2.4em;}.read-offline-fields.sortable .read-offline-repeatable-field-buttons {margin: 0;}}";
    }
    }
    