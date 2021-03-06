<?php
/**
 * Created by PhpStorm.
 * User: Koshala Manojeewa
 * Date: 2/27/19
 * Time: 9:12 AM
 */

class UserDefinedExportsItem extends DataObject
{
    private static $db = array(
        'ManageModelName' => 'Text'
    );

    private static $has_one= array(
        'UserDefinedExportsFieldHolder' => 'UserDefinedExportsFieldHolder'
    );

    private static $has_many = array(
        'UserDefinedExportsButtons' => 'UserDefinedExportsButton'
    );

    private static $summary_fields = array(
        'ManageModelName'
    );

    public function getTitle()
    {
        return $this->ManageModelName;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(array(
            'UserDefinedExportsFieldHolderID',
            'ManageModelName'
        ));

        $fields->addFieldToTab('Root.Main',TextField::create('ManageModelName','Manage Model Name'));
        return $fields;
    }
}