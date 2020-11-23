<?php
/**
 * Created by PhpStorm.
 * User: Koshala Manojeewa
 * Date: 3/11/19
 * Time: 9:59 AM
 */

namespace UserDefinedExports\Forms;

use SilverStripe\ORM\SS_List;

class CsvDataFormatter extends ExcelDataFormatter
{

    /**
     * @inheritdoc
     */
    public function supportedExtensions()
    {
        return array(
            'csv',
        );
    }

    /**
     * @inheritdoc
     */
    public function supportedMimeTypes()
    {
        return array(
            'text/csv',
        );
    }

    /**
     * @inheritdoc
     */
    public function convertDataObjectSet(SS_List $set)
    {
        $this->setHeader();

        $excel = $this->getPhpExcelObject($set);

        $fileData = $this->getFileData($excel, 'Csv');

        return $fileData;
    }
}
