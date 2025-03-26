<?php

namespace Sven\DasForm\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @RouteScope(scopes={"storefront"})
 */
class ContactFormController extends AbstractController
{
    /**
     * @Route("/dasform/contact", name="sven.dasform.contact", methods={"POST"})
     */
    public function handle(Request $request): RedirectResponse
    {
        $email = $request->request->get('email');
        $message = $request->request->get('message');

        // You can log or email this info if needed

        $this->addFlash('success', 'Danke! Ihre Nachricht wurde gesendet.');

        return $this->redirect($request->headers->get('referer') ?? '/');
    }
}
