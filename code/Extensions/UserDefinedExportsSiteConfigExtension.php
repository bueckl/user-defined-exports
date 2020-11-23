<?php
/**
 * Created by PhpStorm.
 * User: Koshala Manojeewa
 * Date: 3/11/19
 * Time: 9:19 AM
 */
namespace UserDefinedExports\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\ORM\DataExtension;
use UserDefinedExports\Model\UserDefinedExportsFieldHolder;

class UserDefinedExportsSiteConfigExtension extends DataExtension
{
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.UserDefinedExports',
            GridField::create(
                'UserDefinedExports',
                'User Defined Exports',
                UserDefinedExportsFieldHolder::get(),
                new GridFieldConfig_RecordEditor(50))
        );
    }
}
