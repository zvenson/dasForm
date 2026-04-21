<?php declare(strict_types=1);

namespace Sven\DasForm\Controller;

use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class InquiryController extends StorefrontController
{
    public function __construct(
        private readonly AbstractMailService $mailService,
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $salutationRepository,
    ) {
    }

    #[Route(
        path: '/dasform/inquiry',
        name: 'sven.dasform.inquiry.send',
        defaults: ['XmlHttpRequest' => true],
        methods: ['POST']
    )]
    public function send(RequestDataBag $data, SalesChannelContext $context): JsonResponse
    {
        try {
            $salesChannelId = $context->getSalesChannel()->getId();

            $recipient = trim((string) $this->systemConfigService->get('SvenDasForm.config.inquiryReceiver', $salesChannelId));
            if ($recipient === '') {
                return $this->alertResponse('danger', 'Kein Empfänger für Produktanfragen konfiguriert. Bitte kontaktieren Sie den Shop-Betreiber.');
            }

            $errors = $this->validate($data);
            if ($errors !== []) {
                return $this->alertResponse('danger', implode(' ', $errors));
            }

            $senderName = (string) $this->systemConfigService->get('core.basicInformation.shopName', $salesChannelId);
            if ($senderName === '') {
                $senderName = 'Shop';
            }

            $salutation = $this->resolveSalutation($data->get('salutationId'), $context->getContext());
            $firstName = $this->trimOrEmpty($data->get('firstName'));
            $lastName = $this->trimOrEmpty($data->get('lastName'));
            $email = $this->trimOrEmpty($data->get('email'));
            $phone = $this->trimOrEmpty($data->get('phone'));
            $subject = $this->trimOrEmpty($data->get('subject')) ?: 'Produktanfrage';
            $comment = $this->trimOrEmpty($data->get('comment'));

            $plain = $this->buildPlainBody($salutation, $firstName, $lastName, $email, $phone, $subject, $comment);
            $html = $this->buildHtmlBody($salutation, $firstName, $lastName, $email, $phone, $subject, $comment);

            $mailDatabag = new DataBag();
            $mailDatabag->set('recipients', [$recipient => 'Produktanfrage']);
            $mailDatabag->set('senderName', $senderName);
            $mailDatabag->set('salesChannelId', $salesChannelId);
            $mailDatabag->set('subject', $subject);
            $mailDatabag->set('contentPlain', $plain);
            $mailDatabag->set('contentHtml', $html);

            if ($email !== '') {
                $mailDatabag->set('replyTo', $email);
            }

            $mailContext = new Context(new SalesChannelApiSource($salesChannelId));

            $this->mailService->send($mailDatabag->all(), $mailContext);

            $this->log(sprintf('dispatched to=%s subject=%s from=%s', $recipient, $subject, $email));

            return $this->alertResponse('success', 'Vielen Dank! Ihre Anfrage wurde erfolgreich gesendet.');
        } catch (\Throwable $e) {
            $this->log(sprintf('FAILED %s: %s', $e::class, $e->getMessage()));

            return $this->alertResponse('danger', 'Leider konnten wir Ihre Anfrage nicht versenden. Bitte versuchen Sie es später erneut.');
        }
    }

    private function log(string $message): void
    {
        @error_log('[SvenDasForm][inquiry] ' . $message);
    }

    /**
     * @return array<int, string>
     */
    private function validate(RequestDataBag $data): array
    {
        $errors = [];
        $firstName = $this->trimOrEmpty($data->get('firstName'));
        $lastName = $this->trimOrEmpty($data->get('lastName'));
        $email = $this->trimOrEmpty($data->get('email'));
        $comment = $this->trimOrEmpty($data->get('comment'));
        $privacy = $data->get('acceptedDataProtection') ?? $data->get('privacy');

        if ($firstName === '') {
            $errors[] = 'Bitte geben Sie Ihren Vornamen an.';
        }
        if ($lastName === '') {
            $errors[] = 'Bitte geben Sie Ihren Nachnamen an.';
        }
        if ($email === '' || !filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse an.';
        }
        if ($comment === '') {
            $errors[] = 'Bitte geben Sie eine Nachricht ein.';
        }
        if (!$privacy) {
            $errors[] = 'Bitte bestätigen Sie die Datenschutzbestimmungen.';
        }

        return $errors;
    }

    private function resolveSalutation(mixed $salutationId, Context $context): string
    {
        if (!is_string($salutationId) || $salutationId === '') {
            return '';
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $salutationId));
        $criteria->setLimit(1);

        $salutation = $this->salutationRepository->search($criteria, $context)->first();
        if ($salutation === null) {
            return '';
        }

        return (string) $salutation->getTranslation('letterName');
    }

    private function trimOrEmpty(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function buildPlainBody(
        string $salutation,
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        string $subject,
        string $comment
    ): string {
        $lines = [
            'Neue Produktanfrage über das Shop-Formular',
            '',
        ];

        if ($salutation !== '') {
            $lines[] = 'Anrede: ' . $salutation;
        }
        $lines[] = 'Name: ' . trim($firstName . ' ' . $lastName);
        $lines[] = 'E-Mail: ' . ($email !== '' ? $email : '-');
        $lines[] = 'Telefon: ' . ($phone !== '' ? $phone : '-');
        $lines[] = '';
        $lines[] = 'Betreff: ' . $subject;
        $lines[] = '';
        $lines[] = 'Nachricht:';
        $lines[] = $comment !== '' ? $comment : '-';

        return implode("\n", $lines) . "\n";
    }

    private function buildHtmlBody(
        string $salutation,
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        string $subject,
        string $comment
    ): string {
        $esc = static fn (string $v): string => htmlspecialchars($v, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');

        $rows = '';
        if ($salutation !== '') {
            $rows .= '<tr><th align="left">Anrede</th><td>' . $esc($salutation) . '</td></tr>';
        }
        $rows .= '<tr><th align="left">Name</th><td>' . $esc(trim($firstName . ' ' . $lastName)) . '</td></tr>';
        $rows .= '<tr><th align="left">E-Mail</th><td>' . $esc($email !== '' ? $email : '-') . '</td></tr>';
        $rows .= '<tr><th align="left">Telefon</th><td>' . $esc($phone !== '' ? $phone : '-') . '</td></tr>';
        $rows .= '<tr><th align="left">Betreff</th><td>' . $esc($subject) . '</td></tr>';

        return '<p><strong>Neue Produktanfrage über das Shop-Formular</strong></p>'
            . '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;">'
            . $rows
            . '</table>'
            . '<p><strong>Nachricht:</strong></p>'
            . '<div style="white-space:pre-wrap;font-family:inherit;">'
            . $esc($comment !== '' ? $comment : '-')
            . '</div>';
    }

    private function alertResponse(string $type, string $message): JsonResponse
    {
        return new JsonResponse([[
            'type' => $type,
            'alert' => $message,
        ]]);
    }
}
