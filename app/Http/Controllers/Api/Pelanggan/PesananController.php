<?php

namespace App\Http\Controllers\Api\Pelanggan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PushNotifController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\VarietasPadi;
use App\StokPadi;
use App\LahanPelanggan;
use App\pelanggan;
use App\Pesanan;

class PesananController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pesanans = DB::table('pesanans')
            ->join('lahan_pelanggans', 'lahan_pelanggans.id', '=', 'pesanans.lahan_pelanggan_id')
            ->join('pelanggans', 'pelanggans.id', '=', 'lahan_pelanggans.pelanggan_id')
            ->where('pelanggans.id', '=', auth()->user()->id)
            ->select('pesanans.*', 'lahan_pelanggans.nama_lahan')
            ->get();

        return response()->json([
            'success'       => 1,
            'message'       => 'Pesanan berhasil :)',
            'pesanans'      => $pesanans
        ]);
    }

    public function getRiwayatMonitoring()
    {
        $pesanans = DB::table('pesanans')
            ->join('lahan_pelanggans', 'lahan_pelanggans.id', '=', 'pesanans.lahan_pelanggan_id')
            ->join('pelanggans', 'pelanggans.id', '=', 'lahan_pelanggans.pelanggan_id')
            ->where('pelanggans.id', '=', auth()->user()->id)
            ->where('status_pesanan', '!=', 'Menunggu Pembayaran')
            ->where('status_pesanan', '!=', 'Lunas')
            ->select('pesanans.*', 'lahan_pelanggans.nama_lahan')
            ->get();

        return response()->json([
            'success'       => 1,
            'message'       => 'Pesanan berhasil :)',
            'pesanans'      => $pesanans
        ]);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $lahanPelanggan = LahanPelanggan::find($request->lahan_pelanggan_id);

        if (empty($lahanPelanggan)) {
            return response()->json([
                'success'       => 0,
                'message'       => 'Lengkapi Informasi Lahan terlebih dahulu)'
            ]);
        } else {
            $stokPadi = DB::table('stok_padis')->where([
                ['id_varietas_padi', $request->varietas_padi],
                ['jumlah_stok', '>', $lahanPelanggan->luas_lahan * 30],
            ])->first();

            if (empty($stokPadi)) {
                return response()->json([
                    'success'       => 0,
                    'message'       => 'Benih Yang Anda pilih Tidak Tersedia)'
                ]);
            } else {

                $jumlahHargaBenih   = $lahanPelanggan->luas_lahan * 30 * $stokPadi->harga_jual_kg;
                $jumlahHargaJasa    = $lahanPelanggan->luas_lahan * 700000;
                $jumlahBiaya        = $jumlahHargaBenih + $jumlahHargaJasa;

                $stok_padi = StokPadi::find($stokPadi->id);
                $stok_padi->jumlah_stok = $stokPadi->jumlah_stok - ($lahanPelanggan->luas_lahan * 30);
                $stok_padi->save();

                $pesanan = new Pesanan;
                $pesanan->tgl_sebar = $request->tgl_sebar;
                $pesanan->tgl_tanam = $request->tgl_tanam;
                $pesanan->total_benih = $lahanPelanggan->luas_lahan * 30;
                $pesanan->total_harga_benih = $jumlahHargaBenih;
                $pesanan->total_harga_jasa = $jumlahHargaJasa;
                $pesanan->total_biaya = $jumlahBiaya;
                $pesanan->lahan_pelanggan_id = $request->lahan_pelanggan_id;
                $pesanan->stok_padi_id = $stokPadi->id;
                $pesanan->status_pesanan = "Menunggu Pembayaran";
                $pesanan->save();

                app(PushNotifController::class)
                    ->pushNotif("Pesanan berhasil",
                        "Pesanan Benih untuk ".$pesanan->lahan_pelanggan->nama_lahan,
                        $pesanan->lahan_pelanggan->pelanggan->fcm);
            }


            return response()->json([
                'success'       => 1,
                'message'       => 'Pesanan berhasil :)',
                'pesanan'     => $pesanan
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $pesanan = Pesanan::Find($id);
        $lahan = $pesanan->lahan_pelanggan->pelanggan;
        $stokPadi = $pesanan->stok_padi->varietas_padi;
        $pemeriksaan_awal = $pesanan->pemeriksaan_awal;


        return response()->json([
            'success'       => 1,
            'message'       => 'Pesanan berhasil :)',
            'pesanan'       => $pesanan
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    // respon handling for error
    public function error($pesan)
    {
        return response()->json([
            'success' => 0,
            'message' => $pesan
        ]);
    }
}
