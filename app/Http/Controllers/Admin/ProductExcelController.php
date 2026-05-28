<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use App\Imports\ProductsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ProductExcelController extends Controller
{
    public function export(Request $request)
    {
        $fields = (array) $request->input('fields', []);

        return Excel::download(
            new ProductsExport($fields),
            'compify-products-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'fields' => ['nullable', 'array'],
            'mode' => ['required', 'in:upsert,create_only,update_only'],
        ]);

        Excel::import(
            new ProductsImport(
                (array) $request->input('fields', []),
                $request->input('mode', 'upsert')
            ),
            $request->file('file')
        );

        return back()->with('success', 'Data produk berhasil diimport.');
    }
}