<?php

namespace Buendon\PwintyPricesBundle\Controller;

use Buendon\PwintyBundle\Catalogue\CatalogueItem;
use Buendon\PwintyBundle\Catalogue\ShippingRatesItem;
use Buendon\PwintyBundle\Service\PwintyService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DefaultController extends Controller
{
    /**
     * @param string $countryCode
     * @param string $quality
     * @param string $shippingBand
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($countryCode, $quality, $shippingBand)
    {
        $service = $this->container->get(PwintyService::NAME);
        $catalogue = $service->getCatalogue($countryCode, $quality);
        $items = $catalogue->getItemsForShippingBandType($shippingBand);
        $shippingRates = $catalogue->getShippingRateForBandType($shippingBand);

        $response = new StreamedResponse();
        $response->setCallback(function() use($items, $shippingRates){

            $handle = fopen('php://output', 'w+');

            fputcsv($handle, array(CatalogueItem::FIELD_NAME,
                CatalogueItem::FIELD_PRICE_GBP,
                CatalogueItem::FIELD_PRICE_USD,
                'Shipping '.ShippingRatesItem::FIELD_IS_TRACKED,
                'Shipping '.ShippingRatesItem::FIELD_PRICE_GBP,
                'Shipping '.ShippingRatesItem::FIELD_PRICE_USD), ';');

            foreach ($items as $item) {
                fputcsv($handle, array($item->getName(), $item->getPriceGBP(), $item->getPriceUSD(),
                    $shippingRates->isTracked(),
                    $shippingRates->getPriceGBP(),
                    $shippingRates->getPriceUSD()
                ),
                    ';');
            }

            fclose($handle);

        });


        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $countryCode.'_'.$quality.'_'.$shippingBand.'.csv'));
        return $response;
    }
}
