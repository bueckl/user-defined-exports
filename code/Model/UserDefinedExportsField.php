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
        'CustomMethod' => 'Varchar(255)',
        'Sort' => 'Int'
    );

    private static $has_one = array(
        'UserDefinedExportsButton' => UserDefinedExportsButton::class
    );

    private static $summary_fields = array(
        'OriginalExportField',
        'ExportFieldLabel',
        'CustomMethod',
        'SelectedType'
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
            'CustomMethod',
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

                        // important for belongs_to
                        $rFields = array_keys($rFields);
                        
                        foreach ($rFields as $rField){
                            $arrRelationFields[] = $relation.'.'.$rField;
                        }
                        $relationDbFields = array_merge($relationDbFields, $arrRelationFields);
                    }
                    $newFieldsArr = array_combine($relationDbFields, $relationDbFields);
                    $arrFields = array_merge($arrFields, $newFieldsArr);
                }



                $relations_belongs_to = Config::inst()->get($className, 'belongs_to', Config::UNINHERITED);


                if ($relations_belongs_to) {

                    $arr = array_keys($relations_belongs_to);
                    foreach ($arr as $relation) {

                        
                        $arrRelationFields = array();
                        $rFields = Injector::inst()->get(DataObjectSchema::class)->databaseFields($relations_belongs_to[$relation]);
                        // important for belongs_to
                        $rFields = array_keys($rFields);

                        foreach ($rFields as $rField){
                            $arrRelationFields[] = $relation.'.'.$rField;
                        }
                        $relationDbFields = array_merge($relationDbFields, $arrRelationFields);
                    }
                    
                    $newFieldsArr = array_combine($relationDbFields, $relationDbFields);
                    $arrFields_belongs_to = array_merge($arrFields, $newFieldsArr);
                }


                
                if (isset($arrFields_belongs_to) && $arrFields_belongs_to) {
                    $arrFields = array_merge( $arrFields, $arrFields_belongs_to);    
                }

                
                $fields->addFieldsToTab('Root.Main', array(
                    DropdownField::create('OriginalExportField', 'Field')
                        ->setSource($arrFields),
                    TextField::create('ExportFieldLabel', 'Custom Label'),
                ));
            }
        } else {
            $buttonObject = UserDefinedExportsButton::get()->filter('ID',$this->UserDefinedExportsButtonID)->first();
            $exportItem = UserDefinedExportsItem::get()->filter('ID', $buttonObject->UserDefinedExportsItemID)->first();
            $class = $exportItem->ManageModelName;
            $object = new $class();

            if($object->hasMethod('customExportMethods')) {
                $dropDownArray = array_merge($this->dbObject('SelectedType')->enumValues(), $object->customExportMethods());
            } else {
                $dropDownArray = $this->dbObject('SelectedType')->enumValues();
            }

            $fields->addFieldsToTab('Root.Main',array(
                DropdownField::create('SelectedType', 'Select the summary field type and save for continue')
                    ->setSource($dropDownArray)
            ));
        }

        return $fields;
    }
}
