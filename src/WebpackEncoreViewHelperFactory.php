<?php

declare(strict_types=1);

namespace WebpackEncoreViewHelper;

use Interop\Container\ContainerInterface;
use Laminas\View\Exception;

use function is_array;

class WebpackEncoreViewHelperFactory
{
    public function __invoke(ContainerInterface $container): WebpackEncoreViewHelper
    {
        $helper = new WebpackEncoreViewHelper();
        $config = $container->get('config');
        if (isset($config['webpack_encore_view_helper_config'])) {
            $configHelper = $config['webpack_encore_view_helper_config'];
            if (
                isset($configHelper['entrypoints_map']['entrypoints'])
                    && is_array($configHelper['entrypoints_map']['entrypoints'])
            ) {
                $helper->setEntrypointsMap($configHelper['entrypoints_map']['entrypoints']);
            } else {
                throw new Exception\RuntimeException('Invalid resource map configuration.');
            }

            if (isset($configHelper['template_entrypoint_map'])) {
                $helper->setTemplateEntrypointMap($configHelper['template_entrypoint_map']);
            }
        }

        return $helper;
    }
}
