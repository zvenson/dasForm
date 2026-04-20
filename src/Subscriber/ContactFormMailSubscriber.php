<?php declare(strict_types=1);

namespace Sven\DasForm\Subscriber;

use Shopware\Core\Content\ContactForm\Event\ContactFormEvent;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContactFormMailSubscriber implements EventSubscriberInterface
{
    private const MARKER_FIELD = 'dasformInquiry';

    public function __construct(
        private readonly AbstractMailService $mailService,
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [ContactFormEvent::class => 'onContactForm'];
    }

    public function onContactForm(ContactFormEvent $event): void
    {
        $data = $event->getContactFormData();

        if (!$data->has(self::MARKER_FIELD)) {
            return;
        }

        $salesChannelId = $event->getSalesChannelId();

        $recipient = trim((string) $this->systemConfigService->get('SvenDasForm.config.inquiryReceiver', $salesChannelId));
        if ($recipient === '') {
            return;
        }

        $senderEmail = (string) $this->systemConfigService->get('core.basicInformation.email', $salesChannelId);
        if ($senderEmail === '') {
            $senderEmail = $recipient;
        }

        $senderName = (string) $this->systemConfigService->get('core.basicInformation.shopName', $salesChannelId);
        if ($senderName === '') {
            $senderName = 'Shop';
        }

        $subject = $this->trimString($data, 'subject') ?: 'Produktanfrage';
        $fullName = trim(($this->trimString($data, 'firstName') ?? '') . ' ' . ($this->trimString($data, 'lastName') ?? ''));

        $body = sprintf(
            "Neue Produktanfrage über das Shop-Formular\n\n".
            "Name: %s\n".
            "E-Mail: %s\n".
            "Telefon: %s\n\n".
            "Betreff: %s\n\n".
            "Nachricht:\n%s\n",
            $fullName !== '' ? $fullName : '-',
            $this->trimString($data, 'email') ?? '-',
            $this->trimString($data, 'phone') ?? '-',
            $subject,
            $this->trimString($data, 'comment') ?? '-',
        );

        $this->mailService->send([
            'recipients' => [$recipient => 'Produktanfrage'],
            'senderName' => $senderName,
            'senderEmail' => $senderEmail,
            'subject' => $subject,
            'contentPlain' => $body,
            'contentHtml' => '<pre style="font-family:inherit;white-space:pre-wrap;">'
                . htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</pre>',
            'salesChannelId' => $salesChannelId,
        ], $event->getContext());
    }

    private function trimString(DataBag $data, string $key): ?string
    {
        $value = $data->get($key);
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
