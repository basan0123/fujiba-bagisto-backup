<?php

namespace Webkul\Pos\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Pos\DataGrids\Admin\BarcodeProductDataGrid;
use Webkul\Pos\Repositories\ProductBarcodeRepository;
use Webkul\Product\Repositories\ProductRepository;

class BarcodeProductController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductBarcodeRepository $productBarcodeRepository,
        protected ProductRepository $productRepository,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(BarcodeProductDataGrid::class)->process();
        }

        return view('pos::admin.barcode-products.index');
    }

    /**
     * Generate Barcode
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateBarcode(int $id)
    {
        // Allow an optional ?barcode_type=code39 query param so the admin can
        // regenerate with a specific symbology from the listing page.
        $type = request()->input('barcode_type') ?: null;

        $result = $this->productBarcodeRepository->generateBarcode($id, $type);

        if (! $result) {
            session()->flash('warning', trans('pos::app.admin.barcode-products.generate-failed'));
        } else {
            session()->flash('success', trans('pos::app.admin.barcode-products.generate-success'));
        }

        return Back();
    }

    /**
     * Update the barcode type for a product and regenerate its barcode.
     */
    public function updateBarcodeType(int $id): JsonResponse
    {
        $type = request()->input('barcode_type') ?: null;

        $allowed = ['code128', 'code128a', 'code128b', 'code39', 'code25', 'codabar'];

        if ($type !== null && ! in_array($type, $allowed, true)) {
            return new JsonResponse(['error' => 'Invalid barcode type.'], 422);
        }

        $this->productBarcodeRepository->generateBarcode($id, $type);

        return new JsonResponse(['message' => trans('pos::app.admin.barcode-products.generate-success')]);
    }

    /**
     * Save a real GTIN/EAN/UPC number for a product and regenerate its barcode image
     * so the image encodes the GTIN value that scanners will read.
     */
    public function updateGtin(int $id): JsonResponse
    {
        $gtin = trim((string) request()->input('gtin'));
        $type = request()->input('barcode_type') ?: null;

        if ($gtin === '') {
            return new JsonResponse(['error' => 'GTIN value is required.'], 422);
        }

        $allowed = ['code128', 'code128a', 'code128b', 'code39', 'code25', 'codabar'];

        if ($type !== null && ! in_array($type, $allowed, true)) {
            return new JsonResponse(['error' => 'Invalid barcode type.'], 422);
        }

        $this->productBarcodeRepository->generateBarcode($id, $type, $gtin);

        return new JsonResponse(['message' => trans('pos::app.admin.barcode-products.generate-success')]);
    }

    /**
     * Print Barcode
     *
     * @return \Illuminate\View\View
     */
    public function printBarcode(int $id)
    {
        $barcode = [];

        $productIds = ($id === 0) ? request()->input('indices') : [$id];

        $products = $this->productRepository->findMany($productIds);

        foreach ($products as $product) {
            $barcode[] = [
                'img_url'      => $this->productBarcodeRepository->getBarcode($product->id),
                'product_name' => $product->name,
            ];
        }

        return view('pos::admin.barcode-products.print')
            ->with('barcode', $barcode);
    }

    /**
     * Mass generate Barcode
     */
    public function massGenerateBarcode(MassDestroyRequest $request): JsonResponse
    {
        $productIds = $request->input('indices');

        foreach ($productIds as $productId) {
            $this->productBarcodeRepository->generateBarcode($productId);
        }

        return new JsonResponse([
            'message' => trans('pos::app.admin.barcode-products.index.datagrid.mass-generate-success'),
        ]);
    }

    /**
     * Mass print Barcode
     *
     * @return \Illuminate\View\View
     */
    public function massPrintBarcode(MassDestroyRequest $request)
    {
        return new JsonResponse([
            'redirect_url' => route('admin.pos.barcode_products.print_barcode', [
                'id'      => 0,
                'indices' => $request->input('indices'),
            ]),
        ]);
    }
}
