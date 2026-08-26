<?php

namespace App\Http\Controllers;

use App\Http\Requests\SelectMetaAssetsRequest;
use App\Services\Meta\MetaAssetSelectionService;
use App\Services\Meta\MetaConnectionLogger;
use Illuminate\Http\RedirectResponse;

class MetaAssetSelectionController extends Controller
{
    public function update(
        SelectMetaAssetsRequest $request,
        MetaAssetSelectionService $selection,
        MetaConnectionLogger $logger,
    ): RedirectResponse {
        $business = $request->user()->currentBusiness;
        $connection = $business->metaConnection;

        $selection->select($business, $request->validated());
        $logger->log($business, 'asset_selection_updated', 'success', 'Meta asset selection updated.', connection: $connection, user: $request->user());

        return redirect()->route('meta-connection.index')->with('status', 'Meta asset selection saved.');
    }
}
