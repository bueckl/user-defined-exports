<?php
/**
 * Created by PhpStorm.
 * User: Koshala Manojeewa
 * Date: 3/11/19
 * Time: 9:27 AM
 */

class UserDefinedExportsModelAdminExtension extends DataExtension
{
    public function updateEditForm($form)
    {

        if($exportItem = UserDefinedExportsItem::get()->filter('ManageModelName',$this->owner->modelClass)->first()) {

            $manageModel = $exportItem->ManageModelName;

            $gridFieldName = $this->sanitiseClassName($manageModel);
            $gridField = $form->Fields()->fieldByName($gridFieldName);
            $gridField->getConfig()->removeComponentsByType('GridFieldExportButton');

            $exportButton = new UserDefinedGridFieldExportButton(
                'buttons-after-left',
                null,
                $this->owner->modelClass
            );

            $gridField->getConfig()->addComponent($exportButton);
        }
    }

    protected function sanitiseClassName($class) {
        return str_replace('\\', '-', $class);
    }
}
