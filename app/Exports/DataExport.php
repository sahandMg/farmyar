<?php

namespace App\Exports;

use App\RemoteData;
use Maatwebsite\Excel\Concerns\FromCollection;

class DataExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return RemoteData::get();

//        foreach ($data as $key => $item){
//
//            $data[$key]->data = unserialize($item->data);
//        }
//        return $data;
    }
}
