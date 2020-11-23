<?php
/**
 * Created by PhpStorm.
 * User: Koshala Manojeewa
 * Date: 2/27/19
 * Time: 9:27 AM
 */
namespace UserDefinedExports\Model;

class UserDefinedExportsButton extends DataObject
{
    private static $db = array(
        'ExportButtonName' => 'Text',
        'ExportFormat' => "Enum('CSV,EXCEL','CSV')",
        'ExportFileName' => 'Text'
    );

    private static $has_one= array(
        'UserDefinedExportsItem' => UserDefinedExportsItem::class
    );

    private static $has_many = array(
        'UserDefinedExportsFields' => UserDefinedExportsField::class
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
            'ExportButtonName',
            'UserDefinedExportsFields',
            'ExportFileName'
        ));

        $fields->addFieldToTab('Root.Main',TextField::create('ExportButtonName','Export Button Name'));
        $fields->addFieldToTab('Root.Main',TextField::create('ExportFileName','Export File Name'));

        DropdownField::create('ExportFormat','ExportFormat')
            ->setSource($this->dbObject('ExportFormat')->enumValues());

        $gridField =  GridField::create(
            'UserDefinedExportsFields',
            'User Defined Exports Fields',
            $this->UserDefinedExportsFields(),
            new GridFieldConfig_RecordEditor(50));

        $fields->addFieldToTab('Root.UserDefinedExportsFields',
            $gridField
        );

        $gridField->getConfig()->addComponent(new GridFieldOrderableRows('Sort'));

        return $fields;
    }
}
