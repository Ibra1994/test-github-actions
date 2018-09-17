<?php

namespace App\Http\Controllers;

use App\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use RealRashid\SweetAlert\Facades\Alert;

class ExcelController extends Controller
{

    public function index()
    {
        return view('welcome');
    }

    public function store(Request $request)
    {
        $validator=Validator::make($request->all(),[
            'file'=>'required',
        ]);
        if ($validator->fails()) {
            \alert()->error('Ошибка','Ошибка. Не указан путь к файлу');
            return redirect('/');
        }

        $xls = $request->file('file');
        $tmpName = $xls->getPathname();
        $xlsPath = str_replace('.tmp', '.xls', $tmpName);
        rename($tmpName, $xlsPath);


        $reader = new Xls();
        $reader->setReadDataOnly(true);
        try {
            $spreadsheet = $reader->load($xlsPath);
            $activeSheet = $spreadsheet->getActiveSheet();
            foreach ($activeSheet->getRowIterator(2) as $row) {
                $report = new Report();
                foreach ($row->getCellIterator('A', 'D') as $cell) {
                    $columnName = $cell->getColumn();
                    $columnValue = $cell->getValue();
                    switch ($columnName) {
                        case ($columnName == 'A'):
                            $report->company_name = $columnValue;
                            break;
                        case ($columnName == 'B'):
                            $report->city = $columnValue;
                            break;
                        case ($columnName == 'C'):
                            $report->batch = $columnValue;
                            break;
                        case ($columnName == 'D'):
                            $date = \DateTime::createFromFormat('m.Y', $columnValue);
                            $report->date = $date->format('Y-m-01');
                            break;
                    }
                }
                $report->save();

            }
            return redirect('/show');
        } catch (\Exception $e) {
            \alert()->error('Ошибка','Ошибка. Формат загруженного файла не верен');
            return redirect('/');
        }

    }

    public function show()
    {
        $report = Report::all();
        return view('show', ['report' => $report]);
    }
}
