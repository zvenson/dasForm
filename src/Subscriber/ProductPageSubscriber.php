<?php

declare(strict_types=1);

namespace Sven\DasForm\Subscriber;

use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Sven\DasForm\Service\RateCalculator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Resolves the per-product inquiry settings and hands them to the storefront as
 * a page extension.
 *
 * The product detail page does not load the `parent` association, and Shopware
 * inherits `customFields` as one whole JSON blob: as soon as a variant carries
 * any custom field of its own, the parent's values are gone. Both together mean
 * a value maintained on the main product is invisible on its variants, so the
 * parent is looked up explicitly and merged underneath the variant's own values.
 */
class ProductPageSubscriber implements EventSubscriberInterface
{
    public const EXTENSION_NAME = 'dasForm';

    private const DEFAULT_INQUIRY_COLOR = '#189eff';
    private const DEFAULT_FINANCING_COLOR = '#16a34a';

    /**
     * Custom field names per button type, plus the fallback button label, the
     * colour setting and the switch that enables the button on every product.
     */
    private const BUTTONS = [
        'inquiry' => [
            'active' => 'sven_dasform_active',
            'text' => 'sven_dasform_button_text',
            'subject' => 'sven_dasform_subject',
            'label' => 'Produktanfrage',
            'colorSetting' => 'inquiryButtonColor',
            'globalSetting' => 'showInquiryOnAllProducts',
            'defaultColor' => self::DEFAULT_INQUIRY_COLOR,
        ],
        'financing' => [
            'active' => 'sven_dasform_financing_active',
            'text' => 'sven_dasform_financing_button_text',
            'subject' => 'sven_dasform_financing_subject',
            'label' => 'Finanzierungsanfrage',
            'colorSetting' => 'financingButtonColor',
            'globalSetting' => 'showFinancingOnAllProducts',
            'defaultColor' => self::DEFAULT_FINANCING_COLOR,
        ],
    ];

    private EntityRepository $productRepository;

    private SystemConfigService $systemConfigService;

    private RateCalculator $rateCalculator;

    public function __construct(
        EntityRepository $productRepository,
        SystemConfigService $systemConfigService,
        RateCalculator $rateCalculator
    ) {
        $this->productRepository = $productRepository;
        $this->systemConfigService = $systemConfigService;
        $this->rateCalculator = $rateCalculator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $product = $page->getProduct();
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();

        $fields = $product->getTranslation('customFields') ?? $product->getCustomFields() ?? [];
        $fields = $this->mergeParentFields($fields, $product->getParentId(), $event->getContext());

        $page->addExtension(self::EXTENSION_NAME, new ArrayStruct([
            'inquiry' => $this->buildButton($fields, 'inquiry', $salesChannelId),
            'financing' => $this->buildButton($fields, 'financing', $salesChannelId),
            'radius' => $this->resolveRadius($salesChannelId),
            'rates' => $this->resolveRates($product, $event->getSalesChannelContext()),
        ]));
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    private function buildButton(array $fields, string $type, string $salesChannelId): array
    {
        $definition = self::BUTTONS[$type];

        $text = trim((string) ($fields[$definition['text']] ?? ''));

        // The global switch turns the button on for every product; the custom
        // field opts single products in while it is off.
        $enabledEverywhere = (bool) $this->systemConfigService->get(
            'SvenDasForm.config.' . $definition['globalSetting'],
            $salesChannelId
        );

        return [
            'active' => $enabledEverywhere || (bool) ($fields[$definition['active']] ?? false),
            'text' => $text,
            'subject' => trim((string) ($fields[$definition['subject']] ?? '')),
            'label' => $text !== '' ? $text : $definition['label'],
            'color' => $this->resolveColor($type, $salesChannelId),
        ];
    }

    private function resolveColor(string $type, string $salesChannelId): string
    {
        $definition = self::BUTTONS[$type];
        $fallback = $definition['defaultColor'];

        $color = trim((string) $this->systemConfigService->get(
            'SvenDasForm.config.' . $definition['colorSetting'],
            $salesChannelId
        ));

        // Only let plain colour literals through — the value ends up in an inline
        // style attribute, so anything else could break out of the declaration.
        return preg_match('/^(#[0-9a-fA-F]{3,8}|rgba?\([0-9,.\s%]+\)|[a-zA-Z]+)$/', $color) === 1
            ? $color
            : $fallback;
    }

    /**
     * Monatsraten fuer die Ratenvorschau am Finanzierungs-Button.
     *
     * Gerechnet wird laut Spezifikation auf den **Nettopreis**. Je nach
     * Verkaufskanal fuehrt Shopware den Einzelpreis brutto oder netto, deshalb
     * wird der Nettobetrag ueber den Steuerzustand bestimmt. Die Umrechnung in
     * Cent ist die einzige Stelle mit Gleitkomma — ab hier ist alles ganzzahlig.
     *
     * @return array<int, array<string, mixed>>
     */
    private function resolveRates(object $product, SalesChannelContext $context): array
    {
        if (!(bool) $this->systemConfigService->get('SvenDasForm.config.showRates', $context->getSalesChannelId())) {
            return [];
        }

        $price = $product->getCalculatedPrice();
        if ($price === null) {
            return [];
        }

        $taxAmount = $price->getCalculatedTaxes()->getAmount();
        $netEuro = $context->getTaxState() === CartPrice::TAX_STATE_GROSS
            ? $price->getUnitPrice() - $taxAmount
            : $price->getUnitPrice();

        $taxRate = $price->getTaxRules()->first()?->getTaxRate() ?? 0.0;

        $salesChannelId = $context->getSalesChannelId();
        $min = $this->systemConfigService->get('SvenDasForm.config.minRatePrice', $salesChannelId);
        $max = $this->systemConfigService->get('SvenDasForm.config.maxRatePrice', $salesChannelId);

        return $this->rateCalculator->calculateAll(
            (int) round($netEuro * 100),
            (float) $taxRate,
            $min === null ? RateCalculator::MIN_PRICE_CENTS : (int) round((float) $min * 100),
            $max === null ? RateCalculator::MAX_PRICE_CENTS : (int) round((float) $max * 100)
        );
    }

    /**
     * Optional corner radius. Empty means: leave it to the theme, which is the
     * normal case — the buttons carry the buy button's classes. It is only
     * needed when a theme rounds its buy button through a selector that does
     * not match an `<a>`, e.g. `button.btn-buy` in a custom.css.
     */
    private function resolveRadius(string $salesChannelId): string
    {
        $radius = trim((string) $this->systemConfigService->get(
            'SvenDasForm.config.buttonBorderRadius',
            $salesChannelId
        ));

        // Ends up in an inline style attribute, so only plain CSS lengths pass.
        return preg_match('/^\d+(\.\d+)?(px|rem|em|%)$/', $radius) === 1 ? $radius : '';
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    private function mergeParentFields(array $fields, ?string $parentId, Context $context): array
    {
        if ($parentId === null) {
            return $fields;
        }

        $missing = [];
        foreach (self::BUTTONS as $definition) {
            foreach (['active', 'text', 'subject'] as $key) {
                if (!isset($fields[$definition[$key]])) {
                    $missing[] = $definition[$key];
                }
            }
        }

        if ($missing === []) {
            return $fields;
        }

        $criteria = new Criteria([$parentId]);
        $criteria->setLimit(1);
        $parent = $this->productRepository->search($criteria, $context)->first();

        if ($parent === null) {
            return $fields;
        }

        $parentFields = $parent->getTranslation('customFields') ?? $parent->getCustomFields() ?? [];

        foreach ($missing as $field) {
            if (isset($parentFields[$field])) {
                $fields[$field] = $parentFields[$field];
            }
        }

        return $fields;
    }
}
