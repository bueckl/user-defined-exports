<?php
/**
 * Created by PhpStorm.
 * User: Koshala Manojeewa
 * Date: 3/11/19
 * Time: 9:30 AM
 */
namespace UserDefinedExports\Forms;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use UserDefinedExports\Model\UserDefinedExportsButton;
use UserDefinedExports\Model\UserDefinedExportsItem;

class UserDefinedGridFieldExportButton extends GridFieldExportButton
{

    protected $exportColumns;

    protected $targetFragment;

    protected $modelClassName;

    protected $exportButtonID;

    protected $useLabelsAsHeaders = null;

    public function __construct($targetFragment = "after", $exportColumns = null, $mClassName)
    {
        $this->targetFragment = $targetFragment;
        $this->exportColumns = $exportColumns;
        $this->modelClassName = $mClassName;
    }


    /**
     * export is an action button
     */
    public function getActions($gridField)
    {
        return array('userdefinedexport');
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        $this->exportButtonID = $data['exportbutton'];
        if(($actionName == 'userdefinedexport') && $this->exportButtonID > 0) {

            if($exportID = $this->exportButtonID) {
                $export = UserDefinedExportsButton::get()->filter('ID',$exportID)->first();
                if($export->ExportFormat == 'EXCEL') {
                    return $this->handleXlsx($gridField, $actionName, 'xlsx');
                } else {
                    return $this->handleCsv($gridField, $actionName, 'csv');
                }
            }
        }
    }


    protected function setHeader($gridField, $ext, $filename = '')
    {
        $do = singleton($gridField->getModelClass());
        if (!$filename) {
            $filename = $do->i18n_plural_name();
        }

        $date = DBDatetime::now();
        $dateVal = explode(" ", $date)[0];
        $timeVal = explode(" ", $date)[1];

        $y = explode("-", $dateVal)[0];
        $m = explode("-", $dateVal)[1];
        $d = explode("-", $dateVal)[2];

        $updateFileName = $filename.'_'.$d.'_'.$m.'_'.$y.'_'.$timeVal;
        Controller::curr()->getResponse()
            ->addHeader(
                "Content-Disposition",
                'attachment; filename="' .
                $updateFileName .
                '.' . $ext . '"'
            );
    }

    public function setUseLabelsAsHeaders($value)
    {
        if ($value === null) {
            $this->useLabelsAsHeaders = null;
        } else {
            $this->useLabelsAsHeaders = (bool)$value;
        }
        return $this;
    }

    public function getUseLabelsAsHeaders()
    {
        return $this->useLabelsAsHeaders;
    }


    public function getHTMLFragments($gridField)
    {

        $custom = Config::inst()->get(UserDefinedGridFieldExportButton::class, 'Base');
        $base = $custom ?: USER_DEFINED_EXPORTS_BASE;
        Requirements::javascript($base . '/javascript/UserDefinedGridFieldExportButton.js');

        $button = new GridField_FormAction(
            $gridField,
            'userdefinedexport',
            _t('TableListField.CSVEXPORT', 'user defined Export to CSV aaa'),
            'userdefinedexport',
            null
        );
        $button->setAttribute('data-icon', 'download-csv');
        $button->setAttribute('data-exportbid', '0');
        $button->addExtraClass('js_export_button');
        $button->setForm($gridField->getForm());

        $exportItem = UserDefinedExportsItem::get()->filter('ManageModelName',$this->modelClassName)->first();

        $exportButtons = $exportItem->UserDefinedExportsButtons();

        $field = DropdownField::create('ExportButtonsd', '')
            ->setSource($exportButtons->map('ID','ExportButtonName'))->setEmptyString('Select Export');

        $field->addExtraClass('no-change-track');
        $field->addExtraClass('user-defined-export-button');

        $data = new ArrayData(array(
            'ClassField' => $field,
            'Export' => '<p class="grid-csv-button">' . $button->Field() . '</p>',
        ));
        return array(
            $this->targetFragment => $data->renderWith(__CLASS__)
        );
    }

    public function getExportButton()
    {
        return UserDefinedExportsButton::get()->filter('ID',$this->exportButtonID)->first();
    }


    protected function getExportColumnsForGridField(GridField $gridField)
    {
        $exportButton = $this->getExportButton();
        $exportFields = $exportButton->UserDefinedExportsFields();
        $fieldsArr = array();

        foreach ($exportFields as $exportField) {
            $label = $exportField->ExportFieldLabel ? $exportField->ExportFieldLabel : '';
            $fieldsArr[$exportField->OriginalExportField] = $label;
        }
        if(!empty($fieldsArr)) {
            $exportColumns = $fieldsArr;
        } else if($dataCols = $gridField->getConfig()->getComponentByType(GridFieldDataColumns::class)) {
            $exportColumns = $dataCols->getDisplayFields($gridField);
        } else {
            $exportColumns = singleton($gridField->getModelClass())->summaryFields();
        }
        return $exportColumns;
    }

    public function handleXlsx(GridField $gridField, $request = null, $ext)
    {
        return $this->genericHandle(ExcelDataFormatter::class, $ext, $gridField, $request);
    }

    public function handleCsv(GridField $gridField, $request = null, $ext)
    {
        return $this->genericHandle(CsvDataFormatter::class, $ext, $gridField, $request);
    }

    protected function genericHandle($dataFormatterClass, $ext, GridField $gridField, $request = null)
    {
        $items = $this->getItems($gridField);

        // Allways filter out test user
        $items = $items->exclude('TestUser', 1);

        $exportButton = $this->getExportButton();
        $this->setHeader($gridField, $ext, $exportButton->ExportFileName);

        $formater = new $dataFormatterClass();
        $formater->setCustomFields($this->getExportColumnsForGridField($gridField));
//        $formater->setUseLabelsAsHeaders($this->useLabelsAsHeaders);
        $fileData = $formater->convertDataObjectSet($items);

        return $fileData;
    }

    protected function getItems(GridField $gridField)
    {
        $gridField->getConfig()->removeComponentsByType(GridFieldPaginator::class);

        $items = $gridField->getManipulatedList();

        foreach ($gridField->getConfig()->getComponents() as $component) {
            if ($component instanceof GridFieldFilterHeader || $component instanceof GridFieldSortableHeader) {
                $items = $component->getManipulatedData($gridField, $items);
            }
        }

        $arrayList = new ArrayList();

        foreach ($items->limit(null) as $item) {
            if (!$item->hasMethod('canView') || $item->canView()) {
                $arrayList->add($item);
            }
        }

        return $arrayList;
    }
}
