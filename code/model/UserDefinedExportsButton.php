<?php
/**
 * Created by PhpStorm.
 * User: Koshala Manojeewa
 * Date: 2/27/19
 * Time: 9:27 AM
 */

class UserDefinedExportsButton extends DataObject
{
    private static $db = array(
        'ExportButtonName' => 'Text',
        'ExportFormat' => "Enum('CSV,EXCEL','CSV')",
    );

    private static $has_one= array(
        'UserDefinedExportsItem' => 'UserDefinedExportsItem'
    );

    private static $has_many = array(
        'UserDefinedExportsFields' => 'UserDefinedExportsField'
    );

    private static $summary_fields = array(
        'ExportButtonName'
    );

    public function getTitle()
    {
        return $this->ExportButtonName;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(array(
            'UserDefinedExportsItemID',
            'ExportButtonName'
        ));

        $fields->addFieldToTab('Root.Main',TextField::create('ExportButtonName','Export Button Name'));

        DropdownField::create('ExportFormat','ExportFormat')
            ->setSource($this->dbObject('ExportFormat')->enumValues());
        return $fields;
    }
}