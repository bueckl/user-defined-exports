<?php
/**
 * Created by PhpStorm.
 * User: Koshala Manojeewa
 * Date: 2/26/19
 * Time: 12:50 PM
 */
namespace UserDefinedExports\Model;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;

class UserDefinedExportsField extends DataObject
{

    private static $db = array(
        'OriginalExportField' => 'Varchar(255)',
        'ExportFieldLabel' => 'Varchar(255)',
        'SelectedType' => "Enum('DB and Relations,Functions','DB and Relations')",
        'Sort' => 'Int'
    );

    private static $has_one = array(
        'UserDefinedExportsButton' => UserDefinedExportsButton::class
    );

    private static $summary_fields = array(
        'OriginalExportField',
        'ExportFieldLabel'
    );

    private static $default_sort = 'Sort';

    private static $table_name = 'UserDefinedExportsField';

    public function validate()
    {
        $result = parent::validate();

        if(
            $this->SelectedType == 'Functions'
            && ($objectType = $this->UserDefinedExportsButton()->UserDefinedExportsItem()->ManageModelName)
            && ($funcName = $this->OriginalExportField)
            && !singleton($objectType)->hasMethod('get'.$funcName)
        ) {
            $result->error('Function doesn\'t exist.', 'bad');
        }

        return $result;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(array(
            'UserDefinedExportsButtonID',
            'SelectedType',
            'ExportFieldLabel',
            'OriginalExportField',
        ));

        if($this->ID) {
            if($this->SelectedType == 'Functions') {
                $fields->addFieldsToTab('Root.Main', array(
                    TextField::create('OriginalExportField', 'Custom method name'),
                    TextField::create('ExportFieldLabel', 'Label name wants to display'),
                ));
            } else {
                $className = $this->UserDefinedExportsButton()->UserDefinedExportsItem()->ManageModelName;
                $dbFields = Injector::inst()->get(DataObjectSchema::class)->databaseFields($className);

                $arr1 = array_keys($dbFields);
                $arrFields = array_combine($arr1, $arr1);

                $relationDbFields = array();
                $relations = Config::inst()->get($className, 'has_one', Config::UNINHERITED);

                if($relations) {
                    $arr = array_keys($relations);
                    foreach ($arr as $relation) {
                        $arrRelationFields = array();
                        $rFields = Injector::inst()->get(DataObjectSchema::class)->databaseFields($relations[$relation]);
                        foreach ($rFields as $rField){
                            $arrRelationFields[] = $relation.'.'.$rField;
                        }
                        $relationDbFields = array_merge($relationDbFields, $arrRelationFields);
                    }
                    $newFieldsArr = array_combine($relationDbFields, $relationDbFields);
                    $arrFields = array_merge($arrFields, $newFieldsArr);
                }
                $fields->addFieldsToTab('Root.Main', array(
                    DropdownField::create('OriginalExportField', 'Field')
                        ->setSource($arrFields),
                    TextField::create('ExportFieldLabel', 'Custom Label'),
                ));
            }
        } else {
            $fields->addFieldsToTab('Root.Main',array(
                DropdownField::create('SelectedType', 'Select the summary field type and save for continue')
                    ->setSource($this->dbObject('SelectedType')->enumValues())
            ));
        }

        return $fields;
    }
}
