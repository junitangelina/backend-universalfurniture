<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    // GET /api/supplier
    public function index()
    {
        return response()->json(Supplier::all());
    }

    // POST /api/supplier
    public function store(Request $request)
    {
        $request->validate([
            'nama_supplier' => 'required|string',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string'
        ]);

        $supplier = Supplier::create($request->only(['nama_supplier', 'alamat', 'telepon']));
        return response()->json($supplier, 201);
    }

    // GET /api/supplier/{id}
    public function show($id)
    {
        $supplier = Supplier::findOrFail($id);
        return response()->json($supplier);
    }

    // PUT /api/supplier/{id}
    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update($request->only(['nama_supplier', 'alamat', 'telepon']));
        return response()->json($supplier);
    }

    // DELETE /api/supplier/{id}
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();
        return response()->json(null, 204);
    }
}
