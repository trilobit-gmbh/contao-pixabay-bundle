<?php

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-pixabay-bundle
 */

namespace Trilobit\PixabayBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Response;
use Trilobit\PixabayBundle\PixabayApi;

/**
 * Handles the Contao frontend routes.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @Route(defaults={"_scope" = "backend", "_token_check" = false})
 */
class PixabayController implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Handles the installation process.
     *
     * @return Response
     *
     * @Route("/contao/pixabay", name="contao_install")
     */
    public function searchAction()
    {
        PixabayApi::search();

        exit;
    }
}
