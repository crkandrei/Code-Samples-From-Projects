<?php

namespace App\Http\Controllers\Admin;


use App\Models\FertilisationPlanDocumentRowInDb;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;


class FertilisationPlanController
{
    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function index(Request $request): Renderable
    {
        return view('admin.fertilisation-plan-document')->with('uuid', $request->route('uuid'));
    }

    public function datatable()
    {
        return datatables()->of(FertilisationPlanDocumentRowInDb::with('document')->whereHas('document', function ($query) {
                $query->where('uuid', $_GET['uuid']);
            }))
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->make(true);
    }
}
