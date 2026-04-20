<?php declare(strict_types=1);

namespace Sven\DasForm;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class SvenDasForm extends Plugin
{
    private const FIELD_SET_NAME = 'sven_dasform_config';

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        $this->addCustomFieldSet($installContext->getContext());
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
        $this->addCustomFieldSet($updateContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->removeCustomFieldSet($uninstallContext->getContext());
    }

    private function addCustomFieldSet(Context $context): void
    {
        /** @var EntityRepository $fieldSetRepository */
        $fieldSetRepository = $this->container->get('custom_field_set.repository');
        /** @var EntityRepository $fieldRepository */
        $fieldRepository = $this->container->get('custom_field.repository');

        $setId = $this->findIdByName($fieldSetRepository, self::FIELD_SET_NAME, $context);

        $customFields = [
            [
                'name' => 'sven_dasform_active',
                'type' => CustomFieldTypes::BOOL,
                'config' => [
                    'label' => [
                        'de-DE' => 'Produktanfrage aktiv?',
                        'en-GB' => 'Product inquiry active?',
                        Defaults::LANGUAGE_SYSTEM => 'Product inquiry active?',
                    ],
                    'componentName' => 'sw-field',
                    'customFieldType' => 'checkbox',
                    'customFieldPosition' => 1,
                ],
            ],
            [
                'name' => 'sven_dasform_button_text',
                'type' => CustomFieldTypes::TEXT,
                'config' => [
                    'label' => [
                        'de-DE' => 'Button-Text / Anfragetext',
                        'en-GB' => 'Button label / inquiry text',
                        Defaults::LANGUAGE_SYSTEM => 'Button label / inquiry text',
                    ],
                    'helpText' => [
                        'de-DE' => 'Ersetzt den Standard-Button-Text "Produktanfrage" und wird zusätzlich im Kommentarfeld des Formulars vorausgefüllt.',
                        'en-GB' => 'Replaces the default "Product inquiry" button label and is prefilled in the form comment field.',
                    ],
                    'componentName' => 'sw-field',
                    'customFieldType' => 'text',
                    'customFieldPosition' => 2,
                ],
            ],
            [
                'name' => 'sven_dasform_subject',
                'type' => CustomFieldTypes::TEXT,
                'config' => [
                    'label' => [
                        'de-DE' => 'Betreff des Formulars',
                        'en-GB' => 'Form subject',
                        Defaults::LANGUAGE_SYSTEM => 'Form subject',
                    ],
                    'helpText' => [
                        'de-DE' => 'Wird als Betreff in das Kontaktformular vorausgefüllt. Leer lassen für den Standardwert des Formulars.',
                        'en-GB' => 'Prefilled as the subject in the contact form. Leave empty to use the form default.',
                    ],
                    'componentName' => 'sw-field',
                    'customFieldType' => 'text',
                    'customFieldPosition' => 3,
                ],
            ],
        ];

        foreach ($customFields as $index => $field) {
            $existingId = $this->findIdByName($fieldRepository, $field['name'], $context);
            if ($existingId !== null) {
                $customFields[$index]['id'] = $existingId;
            }
        }

        $relation = ['entityName' => 'product'];
        if ($setId !== null) {
            /** @var EntityRepository $relationRepository */
            $relationRepository = $this->container->get('custom_field_set_relation.repository');
            $relationCriteria = (new Criteria())
                ->addFilter(new EqualsFilter('customFieldSetId', $setId))
                ->addFilter(new EqualsFilter('entityName', 'product'));
            $existingRelation = $relationRepository->search($relationCriteria, $context)->first();
            if ($existingRelation) {
                $relation['id'] = $existingRelation->getId();
            }
        }

        $payload = [
            'name' => self::FIELD_SET_NAME,
            'config' => [
                'label' => [
                    'de-DE' => 'Produktanfrage',
                    'en-GB' => 'Product inquiry',
                    Defaults::LANGUAGE_SYSTEM => 'Product inquiry',
                ],
            ],
            'customFields' => $customFields,
            'relations' => [$relation],
        ];

        if ($setId !== null) {
            $payload['id'] = $setId;
        }

        $fieldSetRepository->upsert([$payload], $context);
    }

    private function findIdByName(EntityRepository $repository, string $name, Context $context): ?string
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('name', $name));
        $entity = $repository->search($criteria, $context)->first();

        return $entity?->getId();
    }

    private function removeCustomFieldSet(Context $context): void
    {
        /** @var EntityRepository $repository */
        $repository = $this->container->get('custom_field_set.repository');

        $criteria = (new Criteria())->addFilter(new EqualsFilter('name', self::FIELD_SET_NAME));
        $existing = $repository->search($criteria, $context)->first();

        if ($existing) {
            $repository->delete([['id' => $existing->getId()]], $context);
        }
    }
}
