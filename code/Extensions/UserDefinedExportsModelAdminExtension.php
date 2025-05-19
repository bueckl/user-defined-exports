<?php
/**
 * Created by PhpStorm.
 * User: Koshala Manojeewa
 * Date: 3/11/19
 * Time: 9:27 AM
 */
namespace UserDefinedExports\Extensions;

use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\ORM\DataExtension;
use UserDefinedExports\Forms\UserDefinedGridFieldExportButton;
use UserDefinedExports\Model\UserDefinedExportsItem;

class UserDefinedExportsModelAdminExtension extends DataExtension
{
    public function updateEditForm($form)
    {

        if($exportItem = UserDefinedExportsItem::get()->filter('ManageModelName',$this->owner->modelClass)->first()) {

            $manageModel = $exportItem->ManageModelName;

            $gridFieldName = $this->sanitiseClassName($manageModel);
            $gridField = $form->Fields()->fieldByName($gridFieldName);

            if ($gridField) { 
                $gridField->getConfig()->removeComponentsByType(GridFieldExportButton::class);

                $exportButton = new UserDefinedGridFieldExportButton(
                    'after',
                    null,
                    $this->owner->modelClass
                );

                $gridField->getConfig()->addComponent($exportButton);
            } 
        }
    }

    protected function sanitiseClassName($class) {
        return str_replace('\\', '-', $class);
    }
}
