<?php
/**
 * Created by PhpStorm.
 * User: Koshala Manojeewa
 * Date: 3/11/19
 * Time: 10:25 AM
 */
namespace UserDefinedExports\Forms;

use ExcelExport\DataFormatter;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\View\SSViewer;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ExcelDataFormatter extends DataFormatter
{


    private static $api_base = "api/v1/";

    /**
     * Determined what we will use as headers for the spread sheet.
     * @var bool
     */
    protected $useLabelsAsHeaders = null;

    /**
     * @inheritdoc
     */
    public function supportedExtensions()
    {
        return array(
            'xlsx',
        );
    }

    /**
     * @inheritdoc
     */
    public function supportedMimeTypes()
    {
        return array(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
    }

    /**
     * @inheritdoc
     */
    public function convertDataObject(DataObjectInterface $do)
    {
        return $this->convertDataObjectSet(new ArrayList(array($do)));
    }

    /**
     * @inheritdoc
     */
    public function convertDataObjectSet(SS_List $set)
    {
        $this->setHeader();

        $excel = $this->getPhpExcelObject($set);

        $fileData = $this->getFileData($excel, 'Xlsx');

        return $fileData;
    }

    /**
     * Set the HTTP Content Type header to the appropriate Mime Type.
     */
    protected function setHeader()
    {
        Controller::curr()->getResponse()
            ->addHeader("Content-Type", $this->supportedMimeTypes()[0]);
    }

    /**
     * @inheritdoc
     */
    protected function getFieldsForObj($obj)
    {
        $dbFields = array();


        // if custom fields are specified, only select these
        if(is_array($this->customFields)) {



            foreach($this->customFields as $fieldName => $title) {

                // @todo Possible security risk by making methods accessible - implement field-level security
                if($obj->hasField($fieldName) || $obj->hasMethod("get{$fieldName}")) {
                    $dbFields[$fieldName] = $fieldName;
                }

                // RELATION HANDLING JOCHEN
                if($hasOne = $obj->hasOne()) {
                    foreach($hasOne as $relationship => $class) {

                        $parts = explode(".", $fieldName);
                        if (count($parts) == 2) {
                            // It's a relation!
                            $relation = $parts[0];
                            $dbFields[$fieldName] = $parts[1];
                        }
                    }
                }

                 // RELATION HANDLING JOCHEN
                if($hasOne = $obj->belongsTo()) {
                    foreach($hasOne as $relationship => $class) {

                        $parts = explode(".", $fieldName);
                        if (count($parts) == 2) {
                            // It's a relation!
                            $relation = $parts[0];
                            $dbFields[$fieldName] = $parts[1];
                        }
                    }
                }

                if($obj->hasMethod("{$fieldName}")) {
                    $dbFields[$fieldName] = '';
                }


                // END RELATION HANDLING

            }

        } elseif ($obj->hasMethod('getExcelExportFields')) {
            $dbFields = $obj->getExcelExportFields();
        } else {
            // by default, all database fields are selected
            $dbFields = $obj->inheritedDatabaseFields();
        }

        if(is_array($this->customAddFields)) {
            foreach($this->customAddFields as $fieldName) {
                // @todo Possible security risk by making methods accessible - implement field-level security
                if($obj->hasField($fieldName) || $obj->hasMethod("get{$fieldName}")) {
                    $dbFields[$fieldName] = $fieldName;
                }
            }
        }

        // Make sure our ID field is the first one.
        $dbFields = array('ID' => 'Int') + $dbFields;

        if(is_array($this->removeFields)) {
            $dbFields = array_diff_key($dbFields, array_combine($this->removeFields,$this->removeFields));
        }

        return $dbFields;
    }

    /**
     * Generate a {@link PHPExcel} for the provided DataObject List
     * @param  SS_List $set List of DataObjects
     * @return PHPExcel
     */
    public function getPhpExcelObject(SS_List $set)
    {
        // Get the first object. We'll need it to know what type of objects we
        // are dealing with
        $first = $set->first();



        // Get the Excel object
        $excel = $this->setupExcel($first);
        $sheet = $excel->setActiveSheetIndex(0);

        // Make sure we have at lease on item. If we don't, we'll be returning
        // an empty spreadsheet.
        if ($first) {

            // Set up the header row
            $fields = $this->getFieldsForObj($first);
            $allFields = [];

            foreach ($fields as $field => $label) {
                if($first->hasMethod("{$field}")) {
                    foreach ($first->$field() as $customField => $value) {
                        $allFields[$customField] = '';
                    }
                } else {
                    $allFields[$field] = $label;
                }
            }




            $this->headerRow($sheet, $allFields, $first);

            // Add a new row for each DataObject
            foreach ($set as $item) {
                $this->addRow($sheet, $item, $fields);
            }

            // Freezing the first column and the header row
            $sheet->freezePane("B2");
            // Auto sizing all the columns
            $col = sizeof($fields);
            for ($i = 1; $i <= $col; $i++) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))
                    ->setAutoSize(true);
            }

        }
        return $excel;
    }

    /**
     * Initialize a new {@link PHPExcel} object based on the provided
     * {@link DataObjectInterface} interface.
     * @param  DataObjectInterface $do
     * @return PHPExcel
     */
    protected function setupExcel(DataObjectInterface $do)
    {
        // Try to get the current user
        $member = Security::getCurrentUser();
        $creator = $member ? $member->getName() : '';

        // Get information about the current Model Class
        $singular = $do ? $do->i18n_singular_name() : '';
        $plural = $do ? $do->i18n_plural_name() : '';

        // Create the Spread sheet
        $excel = new Spreadsheet();

        $excel->getProperties()
            ->setCreator($creator)
            ->setTitle(_t(
                'firebrandhq.EXCELEXPORT',
                '{singular} export',
                'Title for the spread sheet export',
                array('singular' => $singular)
            ))
            ->setDescription(_t(
                'firebrandhq.EXCELEXPORT',
                'List of {plural} exported out of a SilverStripe website',
                'Description for the spread sheet export',
                array('plural' => $plural)
            ));

        // Give a name to the sheet
        if ($plural) {
            $excel->getActiveSheet()->setTitle($plural);
        }

        return $excel;
    }


    protected function headerRow(Worksheet &$sheet, array $fields, DataObjectInterface $do)
    {
        // Counter
        $row = 1;
        $col = 1;

        $useLabelsAsHeaders = $this->getUseLabelsAsHeaders();

        // Add each field to the first row
        $customFields = $this->customFields;

        foreach ($fields as $field => $type) {
            // debug::dump( $customFields[$field] );
            // debug::dump( $customFields[$type] );
            //$header = $useLabelsAsHeaders ? $do->fieldLabel($field) : $field;

            if(array_key_exists($field, $customFields)) {
                $fieldLabel = $customFields[$field] != null ? $customFields[$field] : $do->fieldLabel($field);
            } else {
                $fieldLabel = $do->fieldLabel($field);
            }
            $header = $fieldLabel;
            $sheet->setCellValueByColumnAndRow($col, $row, $header);
            $col++;
        } //die();
        // Get the last column
        $col--;
        $endcol = Coordinate::stringFromColumnIndex($col);
        // Set Autofilters and Header row style
        $sheet->setAutoFilter("A1:{$endcol}1");
        $sheet->getStyle("A1:{$endcol}1")->getFont()->setBold(true);
        return $sheet;
    }

    /**
     * Add a new row to a {@link PHPExcel_Worksheet} based of a
     * {@link DataObjectInterface}
     * @param PHPExcel_Worksheet  $sheet
     * @param DataObjectInterface $item
     * @param array               $fields List of fields to include
     * @return PHPExcel_Worksheet
     */
    protected function addRow(
        Worksheet &$sheet,
        DataObjectInterface $item,
        array $fields
    ) {
        $row = $sheet->getHighestRow() + 1;
        $col = 1;

        foreach ($fields as $field => $type) {
            if ($item->hasField($field) || $item->hasMethod("get{$field}")) {
                $value = $item->$field;
                
                if($field == 'Cell' || $field == 'Phone' || $field == 'UDID') {
                    $sheet->setCellValueExplicitByColumnAndRow($col, $row, $value, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValueByColumnAndRow($col, $row, $value);
                }
                
            } elseif ($item->hasMethod("{$field}")) {
                $arrayData = $item->$field();
                $i = 0;
                foreach ($arrayData as $dataField => $dataValue) {
                    $value = $dataValue;
                    $sheet->setCellValueByColumnAndRow($col, $row, $value);
                    if($i != count($arrayData) - 1) {
                        $col++;
                    }
                    $i++;
                }

            } else {
                $viewer = SSViewer::fromString('$' . $field . '.RAW');
                $value = $item->renderWith($viewer, true);
                $sheet->setCellValueByColumnAndRow($col, $row, $value);
            }

            $col++;
        }

        return $sheet;
    }

    /**
     * Generate a string representation of an {@link PHPExcel} spread sheet
     * suitable for output to the browser.
     * @param  PHPExcel $excel
     * @param  string   $format Format to use when outputting the spreadsheet.
     * Must be compatible with the format expected by
     * {@link PHPExcel_IOFactory::createWriter}.
     * @return string
     */
    protected function getFileData($excel, $format)
    {
        $writer = IOFactory::createWriter($excel, $format);
        ob_start();
        $writer->save('php://output');
        $fileData = ob_get_clean();

        return $fileData;
    }

    /**
     * Accessor for UseLabelsAsHeaders. If this is `true`, the data formatter will call {@link DataObject::fieldLabel()} to pick the header strings. If it's set to false, it will use the raw field name.
     *
     * You can define this for a specific ExcelDataFormatter instance with `setUseLabelsAsHeaders`. You can set the default for all ExcelDataFormatter instance in your YML config file:
     *
     * ```
     * ExcelDataFormatter:
     *   UseLabelsAsHeaders: true
     * ```
     *
     * Otherwise, the data formatter will default to false.
     *
     * @return bool
     */
    public function getUseLabelsAsHeaders()
    {
        if ($this->useLabelsAsHeaders !== null) {
            return $this->useLabelsAsHeaders;
        }

        $useLabelsAsHeaders = $this->useLabelsAsHeaders;
        if ($useLabelsAsHeaders !== null) {
            return $useLabelsAsHeaders;
        }

        return false;
    }

    /**
     * Setter for UseLabelsAsHeaders. If this is `true`, the data formatter will call {@link DataObject::fieldLabel()} to pick the header strings. If it's set to false, it will use the raw field name.
     *
     * If `$value` is `null`, the data formatter will fall back on whatevr the default is.
     * @param bool $value
     * @return ExcelDataFormatter
     */
    public function setUseLabelsAsHeaders($value)
    {
        if ($value === null) {
            $this->useLabelsAsHeaders = null;
        } else {
            $this->useLabelsAsHeaders = (bool)$value;
        }
        return $this;
    }
}
