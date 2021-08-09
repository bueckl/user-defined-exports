<?php
namespace UserDefinedExports\Forms;

use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\ORM\ValidationResult;
use UserDefinedExports\Model\UserDefinedExportsField;

class GridFieldUserDefinedExportsFieldsDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest {

    private static $allowed_actions = [
        'ItemEditForm'
    ];

    public function doSave($data, $form)
    {
        $type = isset($data['SelectedType']) ? $data['SelectedType'] : '';
        if($type == 'Pre defined columns') {

            // Check permission
            if (!$this->record->canEdit()) {
                $this->httpError(403, _t(
                    __CLASS__ . '.EditPermissionsFailure',
                    'It seems you don\'t have the necessary permissions to edit "{ObjectTitle}"',
                    ['ObjectTitle' => $this->record->singular_name()]
                ));
                return null;
            }

            $class = $this->record->ClassName;
            $object = new $class();
            $arr = $object::config()->user_defined_export_column_mapping;
            $button = $this->record->UserDefinedExportsButtonID;

            foreach ($arr as $key => $item) {
                $exportField = new UserDefinedExportsField();
                $exportField->SelectedType = $type;
                $exportField->UserDefinedExportsButton = $button;
                $exportField->OriginalExportField = $key;
                $exportField->ExportFieldLabel = $item;
                $exportField->write();
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

            $form->sessionMessage($message, 'good', ValidationResult::CAST_HTML);
            // Redirect after save
            $controller = $this->getToplevelController();
            $controller->getRequest()->addHeader('X-Pjax', 'Content');
            return $controller->redirect($this->getBackLink(), 302);

        } else {
            Parent::doSave($data, $form);
        }

    }
}
