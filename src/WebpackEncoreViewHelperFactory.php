<?php
namespace WebpackEncoreViewHelper;

use Laminas\View\Exception;
use Interop\Container\ContainerInterface;

class WebpackEncoreViewHelperFactory {

    public function __invoke(ContainerInterface $container )
    {

        $helper= new WebpackEncoreViewHelper();

        $config = $container->get('config');
        if (isset($config['webpack_encore_view_helper_config'])) {
            $configHelper = $config['webpack_encore_view_helper_config'];

            if (isset($configHelper['entrypoints_map']['entrypoints']) && is_array($configHelper['entrypoints_map']['entrypoints'])) {
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
