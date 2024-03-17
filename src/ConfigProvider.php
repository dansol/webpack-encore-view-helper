<?php

declare(strict_types=1);

namespace WebpackEncoreViewHelper;

use WebpackEncoreViewHelper\WebpackEncoreViewHelper;
use WebpackEncoreViewHelper\WebpackEncoreViewHelperFactory;

/**
 * The configuration provider for the ZecLibrary module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'view_helpers' => $this->getViewHelpers(),
        ];
    }

    /**
     * Returns view helpers
     *
     * @return array
     */
    public function getViewHelpers()
    {
        return [
            //zend-servicemanager-style configuration for adding view helpers:
            'aliases' => [
                'webpackEncoreAssets'   => WebpackEncoreViewHelper::class,
                'webpack_encore_assets' => WebpackEncoreViewHelper::class,
            ],
            //- 'invokables'
            //- 'factories'
            'factories' => [
                WebpackEncoreViewHelper::class => WebpackEncoreViewHelperFactory::class,
            ],
        ];
    }
}
