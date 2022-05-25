<?php

namespace App\HttpController\Business\Admin\TenderingAndBidding;

use App\HttpController\Models\Api\AntAuthList;

class TenderingAndBiddingController extends TenderingAndBiddingBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getList(): bool
    {
        $entname = $this->getRequestData('entname');
        $status = $this->getRequestData('status');
        empty($status) ?: $status = jsonDecode($status);

        $orm = AntAuthList::create();

        if (!empty($entname)) {
            $orm->where('entName', "%{$entname}%", 'LIKE');
        }

        if (!empty($status)) {
            $orm->where('status', $status, 'IN');
        }

        return $this->writeJson(200, null, $orm->all());
    }

}