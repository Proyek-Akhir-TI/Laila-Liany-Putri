<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\VarietasPadi;

class DftvarietasController extends Controller
{
    //
    public function index()
    {
        $varietas = VarietasPadi::orderBy('id', 'Desc')->get();
        return view('dftvarietaspadi', compact('varietas'));
        
    }

    public function formvarietas()
    {
    
        return view('tambahvarietas');
    }

    public function cari(Request $request)
    {
        $cari = $request->input('cari');
        $varietas = VarietasPadi::where('nama_varietas', 'like', "%" . $cari ."%")->get();
        
        return view('dftvarietaspadi', compact('varietas'));
    }
    
    public function store(Request $request)
    {
        VarietasPadi::create([
            'nama_varietas' => $request->nama_varietas,
            'deskripsi_varietas' => $request->deskripsi_varietas,
            // 'foto' => $request->nama_lengkap,
        ]);
        return redirect('/admin/dftvarietaspadi');
    }

    public function edit($id)
    {
        $data = VarietasPadi::where('id',$id)->first();

        return view('editvarietas',['detail'=>$data]);
    }

    public function update(Request $request, $id)
    {
        $varietas = VarietasPadi::findorfail($id);

        $varietas->nama_varietas = $request->nama_varietas;
        $varietas->deskripsi_varietas = $request->deskripsi_varietas;
        $varietas->foto_varietas = $request->foto_varietas;

        $varietas->save();
        
        return redirect('admin/dftvarietaspadi');
    }
}
