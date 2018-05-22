<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2014 Leo Feyer
 *
 * @package     Trilobit
 * @author      trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license     LGPL-3.0-or-later
 * @copyright   trilobit GmbH
 */

namespace Trilobit\PixabayBundle\Controller;

use Contao\Encryption;
use Contao\Environment;
use Contao\InstallationBundle\Config\ParameterDumper;
use Contao\InstallationBundle\Database\AbstractVersionUpdate;
use Contao\InstallationBundle\Database\ConnectionFactory;
use Contao\InstallationBundle\Event\ContaoInstallationEvents;
use Contao\InstallationBundle\Event\InitializeApplicationEvent;
use Doctrine\DBAL\DBALException;
use Patchwork\Utf8;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
