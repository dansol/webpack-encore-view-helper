<?php

/**
 * Webpack encore view helper
 */

declare(strict_types=1);

namespace WebpackEncoreViewHelper;

use Laminas\View\Exception;
use Laminas\View\Helper\AbstractHelper;
use Laminas\View\Helper\Asset;
use Laminas\View\Model\ViewModel;

use function array_key_exists;
use function array_merge;
use function array_push;
use function array_unique;
use function is_array;
use function method_exists;
use function pathinfo;
use function preg_match;
use function sprintf;
use function substr;

use const PATHINFO_EXTENSION;

class WebpackEncoreViewHelper extends AbstractHelper
{
    /** @var array */
    protected $entrypointsMap = [];

    /** @var array */
    protected $templateEntrypointMap = [];

    /** @var array */
    private $jsAssets = [];

    /** @var array */
    private $cssAssets = [];

    /** @var array */
    private $jsRootAssetFiles = [];

    /** @var array */
    private $cssRootAssetFiles = [];

    /** @var ViewModel */
    private $layout;

    /**
     * @return $this
     * @throws Exception\InvalidArgumentException
     */
    public function __invoke(?string $webPackEntryPoint = null, ?string $type = null)
    {
        if ($webPackEntryPoint === null) {
            return $this;
        }

        if (substr($webPackEntryPoint, 0, 4) === 'auto' && $this->isRoot() === false) {
            throw new Exception\InvalidArgumentException(
                'helper with "auto*" parameter can be use only from root(layout)'
            );
        }

        $layout = $this->getLayout();
        if ($webPackEntryPoint === 'auto' || $webPackEntryPoint === 'auto-route') {
            $templateFullName = $layout->getTemplate();
            $wpEntryPoint     = $this->getWebpackentryFromTemplate($templateFullName);
            $this->loadAssets($wpEntryPoint, $type);
        }

        if ($webPackEntryPoint === 'auto' || $webPackEntryPoint === 'auto-child') {
            // find template name from child view ( assumed captureTo is "content")
            $childModel = $layout->getChildrenByCaptureTo('content', false);
            if (! is_array($childModel)) {
                throw new Exception\InvalidArgumentException('cannot detect child view model template name');
            }

            $templateFullName = $childModel[0]->getTemplate();
            $wpEntryPoint     = $this->getWebpackentryFromTemplate($templateFullName);
            $this->loadAssets($wpEntryPoint, $type);
        }

        if (substr($webPackEntryPoint, 0, 4) !== 'auto') {
            $this->loadAssets($webPackEntryPoint, $type);
        }
    }

    private function getWebpackentryFromTemplate(string $templateFullName): string
    {
        $webpackEntrypoint = '';
        $bOk               = false;

        // First tentative: from template/entrypoint map
        if (isset($this->templateEntrypointMap[$templateFullName])) {
            $webpackEntrypoint = $this->templateEntrypointMap[$templateFullName];
            $bOk               = true;
        }

        // Second tentative:if not found at step 1 then try to assume template name equal to entrypoint
        if ($bOk === false) {
            $matches = [];
            // resolve mezzio template (namspace::template)
            if (preg_match('#^(?P<namespace>[^:]+)::(?P<template>.*)$#', $templateFullName, $matches)) {
                $webpackEntrypoint = $matches['template'];
            }
        }

        return $webpackEntrypoint;
    }

    private function loadAssets(string $webPackEntryPoint, ?string $type = null): void
    {
        if ($webPackEntryPoint === '*exclude') {
            return;
        }

        if ($type === null) {
            $this->loadAssets($webPackEntryPoint, 'css');
            $this->loadAssets($webPackEntryPoint, 'js');
            return;
        }

        if (is_array($webPackEntryPoint)) {
            foreach ($webPackEntryPoint as $entryPoint) {
                $this->loadAssets($entryPoint, $type);
            }
            return;
        }

        $assetFiles = [];
        if (! array_key_exists($webPackEntryPoint, $this->entrypointsMap)) {
            // @codingStandardsIgnoreStart
            throw new Exception\InvalidArgumentException(
                'Entrypoint with name:(' . $webPackEntryPoint . ') is not defined.
                If you are using "auto*" parameter you need to map entry point to template in webpack_encore_view_helper_config -> template_entrypoint_map configuration
                or change entrypoint name in order to match template name with entrypoint name.
                If you are not using "auto*" parameter check entry point name"
            '
            );
            // @codingStandardsIgnoreEnd
        } else {
            $asset = $this->entrypointsMap[$webPackEntryPoint];
        }

        if (isset($asset[$type])) {
            foreach ($asset[$type] as $element) {
                array_push($assetFiles, $element);
            }
        }

        $isRoot = $this->isRoot();

        if ($type === 'js' && $isRoot === false) {
            $this->jsAssets = $assetFiles;
        }

        if ($type === 'css' && $isRoot === false) {
            $this->cssAssets = $assetFiles;
        }

        if ($type === 'js' && $isRoot === true) {
            $this->jsRootAssetFiles = array_merge($this->jsRootAssetFiles, $assetFiles);
        }

        if ($type === 'css' && $isRoot === true) {
            $this->cssRootAssetFiles = array_merge($this->cssRootAssetFiles, $assetFiles);
        }
    }

    public function render(string $type): string
    {
         $output = '';

        if ($type === 'js') {
            $model           = '<script src="%s"></script>';
            $assetFilesChild = $this->jsAssets;
            $assetFilesRoot  = $this->jsRootAssetFiles;
        }
        if ($type === 'css') {
            $model           = '<link rel="stylesheet" href="%s">';
            $assetFilesChild = $this->cssAssets;
            $assetFilesRoot  = $this->cssRootAssetFiles;
        }

        $assets = array_merge($assetFilesRoot, $assetFilesChild);
        $assets = array_unique($assets);

        foreach ($assets as $asset) {
            $output .= "\r\n" . sprintf($model, $asset);
        }

        return $output;
    }

    private function getLayout(): ?ViewModel
    {
        if ($this->layout !== null) {
            return $this->layout;
        }

        /* retrieve viewModelHelper in order to get current view model and its root. Root=layout */
        if (method_exists($this->getView(), 'plugin')) {
            /** @var \Laminas\View\Helper\ViewModel $viewModelHelper */
            $viewModelHelper = $this->view->plugin('view_model');
            $this->layout    = $viewModelHelper->getRoot();
        }

        return $this->layout;
    }

    private function isRoot(): bool
    {
        $isRoot = false;

        $viewModelHelper = $this->view->plugin('view_model');
        if ($this->getLayout() === $viewModelHelper->getCurrent()) {
            $isRoot = true;
        }

        return $isRoot;
    }

    /**
     * Laminas asset view helper but return full link or script tags
     */
    public function asset(string $asset): string
    {
        $model = '';

        /** @var Asset $assetHelper */
        $assetHelper = $this->getView()->plugin('asset');

        $assetResult = $assetHelper($asset);

        $ext = pathinfo($assetResult, PATHINFO_EXTENSION);

        if ($ext === 'js') {
            $model = '<script src="%s"></script>';
        }
        if ($ext === 'css') {
            $model = '<link rel="stylesheet" href="%s">';
        }

        return sprintf($model, $assetResult);
    }

    public function setTemplateEntrypointMap(array $templateEntrypointMap): void
    {
        $this->templateEntrypointMap = $templateEntrypointMap;
    }

    /**
     * @param array $entrypointsMap
     */
    public function setEntrypointsMap($entrypointsMap)
    {
        $this->entrypointsMap = $entrypointsMap;
    }
}
