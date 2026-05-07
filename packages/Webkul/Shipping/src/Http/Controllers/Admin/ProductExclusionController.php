<?php

namespace Webkul\Shipping\Http\Controllers\Admin;

use Illuminate\Routing\Controller;

class ProductExclusionController extends Controller
{
    public function index()         { return view('shipping::admin.exclusions.index'); }
    public function create()        { return view('shipping::admin.exclusions.index'); }
    public function store()         { return redirect()->back(); }
    public function edit($id)       { return view('shipping::admin.exclusions.index'); }
    public function update($id)     { return redirect()->back(); }
    public function destroy($id)    { return redirect()->back(); }
}
