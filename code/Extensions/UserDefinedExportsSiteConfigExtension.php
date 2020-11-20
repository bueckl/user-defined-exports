<?php
/**
 * Created by PhpStorm.
 * User: Koshala Manojeewa
 * Date: 3/11/19
 * Time: 9:19 AM
 */
namespace UserDefinedExports\Extensions;

use SilverStripe\ORM\DataExtension;

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
