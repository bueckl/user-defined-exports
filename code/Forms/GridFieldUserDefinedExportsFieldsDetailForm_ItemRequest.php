<?php
namespace UserDefinedExports\Forms;

use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\ORM\ValidationResult;
use UserDefinedExports\Model\UserDefinedExportsButton;
use UserDefinedExports\Model\UserDefinedExportsField;
use UserDefinedExports\Model\UserDefinedExportsItem;

class GridFieldUserDefinedExportsFieldsDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest {

    private static $allowed_actions = [
        'ItemEditForm'
    ];

    public function doSave($data, $form)
    {

        $type = isset($data['SelectedType']) ? $data['SelectedType'] : '';

        if ($type=="EventModuleSlotsToArray") {
            
        }

        if($type && $type !== 'DB and Relations' &&  $type !== 'Functions') {
            // Check permission
            if (!$this->record->canEdit()) {
                $this->httpError(403, _t(
                    __CLASS__ . '.EditPermissionsFailure',
                    'It seems you don\'t have the necessary permissions to edit "{ObjectTitle}"',
                    ['ObjectTitle' => $this->record->singular_name()]
                ));
                return null;
            }

            $button = $this->record->UserDefinedExportsButtonID;

            $buttonObject = UserDefinedExportsButton::get()->filter('ID',$button)->first();
            $exportItem = UserDefinedExportsItem::get()->filter('ID', $buttonObject->UserDefinedExportsItemID)->first();

            $class = $exportItem->ManageModelName;
            $object = new $class();
            $arr = $object->$type();
            

            if(!empty($arr)) {
                foreach ($arr as $key => $item) {
                    $existingField = UserDefinedExportsField::get()->filter([
                        'UserDefinedExportsButtonID' => $button,
                        'OriginalExportField' => $key,
                    ])->first();
                    if($existingField) {
//                        $existingField->update([
//                            'SelectedType' => $type,
//                            'UserDefinedExportsButtonID' => $button,
//                            'ExportFieldLabel' => '', //we have to keep this empty
//                        ]);
//                        $existingField->write();
                    } else {
                        $exportField = new UserDefinedExportsField();
                        $exportField->CustomMethod = $type;
                        $exportField->SelectedType = ''; //cant add because not available enum
                        $exportField->UserDefinedExportsButtonID = $button;
                        $exportField->OriginalExportField = $key;
                        $exportField->ExportFieldLabel = '';
                        $exportField->write();
                    }
                }

                $link = '<a href="' . $this->Link('edit') . '">"'
                    . htmlspecialchars($this->record->Title, ENT_QUOTES)
                    . '"</a>';
                $message = _t(
                    'SilverStripe\\Forms\\GridField\\GridFieldDetailForm.Saved',
                    'Saved {name} {link}',
                    [
                        'name' => $this->record->i18n_singular_name(),
                        'link' => $link
                    ]
                );

                $messageType = 'good';
                $result = ValidationResult::CAST_HTML;
            } else {
                $message = 'There are is array data';
                $messageType = 'error';
                $result = ValidationResult::CAST_TEXT;
            }

            $form->sessionMessage($message, $messageType, $result);


            // Redirect after save
            $controller = $this->getToplevelController();
            $controller->getRequest()->addHeader('X-Pjax', 'Content');
            return $controller->redirect($this->getBackLink(), 302);

        } else {
            Parent::doSave($data, $form);
        }

    }
}
