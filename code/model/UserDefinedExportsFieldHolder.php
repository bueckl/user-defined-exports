<?php
/**
 * Created by PhpStorm.
 * User: Koshala Manojeewa
 * Date: 2/26/19
 * Time: 12:27 PM
 */

class UserDefinedExportsFieldHolder extends DataObject
{
    private static $db = array(
        'ModelAdminName' => 'Text'
    );

    private static $has_many = array(
        'UserDefinedExportsItems' => 'UserDefinedExportsItem'
    );

    private static $summary_fields = array(
        'ModelAdminName'
    );

    public function getTitle()
    {
        return $this->ModelAdminName;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $objArr = SS_ClassLoader::instance()->getManifest()->getDescendantsOf('ModelAdmin');

        $combinedArr = array_combine($objArr, $objArr);
        $fields->addFieldsToTab('Root.Main', array(
            DropdownField::create('ModelAdminName', 'Model Admin Name')->setSource($combinedArr),
        ));

        return $fields;
    }

    public function onAfterWrite()
    {
        $modelAdminName = $this->ModelAdminName;
        $modelAdmin = $modelAdminName::create();
        $managedModels = $modelAdmin->getManagedModels();

        foreach ($managedModels as $key => $managedModel) {
            $exportItem = UserDefinedExportsItem::create();
            $exportItem->ManageModelName = $key;
            $exportItem->UserDefinedExportsFieldHolderID = $this->ID;
            $exportItem->write();
        }
    }
}