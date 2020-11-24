<?php
/**
 * Created by PhpStorm.
 * User: Koshala Manojeewa
 * Date: 2/27/19
 * Time: 9:27 AM
 */
namespace UserDefinedExports\Model;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

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

    private static $table_name = 'UserDefinedExportsButton';

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
