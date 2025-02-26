<?php

namespace App\Http\Controllers\Utils\Tools;

use App\Http\Controllers\Controller;
use Image;

class ToolsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

   /**
     * Fungtion show button for export or print.
     *
     * @param $columnsArrExPr
     * @return array[]
     */
    public function buttonDatatables($columnsArrExPr)
    {
        return [
            [
                'reload'
            ],
            [
                'pageLength'
            ],
            [
                'extend' => 'csvHtml5',
                'exportOptions' => [
                    'columns' => $columnsArrExPr
                ]
            ],
            [
                'extend' => 'pdfHtml5',
                'orientation' => 'potrait',
                'pageSize' => 'A3',
                'exportOptions' => [
                    'columns' => $columnsArrExPr
                ]
            ],
            [
                'extend' => 'excelHtml5',
                'exportOptions' => [
                    'columns' => $columnsArrExPr
                ]
            ],
            [
                'extend' => 'print',
                'orientation' => 'potrait',
                'pageSize' => 'A3',
                'exportOptions' => [
                    'columns' => $columnsArrExPr
                ]
            ],
        ];
    }

    /**
     * Summary of printColumnArr
     * @param mixed $from
     * @param mixed $to
     * @return array
     */
    public function ExportColumnArr($from, $to)
    {
        $columnsArrExPr = [];
        $iMax = $to;
        for ($i = $from; $i < $iMax; $i++) {
            $columnsArrExPr[] = $i;
        }

        return $columnsArrExPr;
    }

    /**
     * Summary of resizePhoto
     * @return void
     */
    public function resizePhoto($pathImage, $sizeImage){

        // the image will be replaced with an optimized version which should be smaller
        $img =  Image::make($pathImage);

        // Resize the instance
        $img->resize($sizeImage, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        // Save the image
        $img->save($pathImage);
    }
}
