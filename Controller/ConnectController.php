<?php

namespace Da\OAuthClientBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class ConnectController extends ContainerAware
{
    /**
     * @Route("/disconnect", name="disconnect")
     * @Template()
     */
    public function disconnectAction(Request $request)
    {
        $token = $this->container->get('security.context')->getToken();
        $resourceOwnerName = $token->getResourceOwnerName();
        $resourceOwner = $this->container->get('hwi_oauth.resource_owner.'.$resourceOwnerName);

        $disconnectionUrl = $resourceOwner->getDisconnectionUrl(
            $this->container->get('router')->generate('logout', array(), true)
        );

        return new RedirectResponse($disconnectionUrl);
    }
}
