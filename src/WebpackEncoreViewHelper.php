<?php

/**
 * Webpack encore view helper
 *
 * @author dansol
 */

namespace WebpackEncoreViewHelper;

use Laminas\View\Exception;
use Laminas\View\Helper\AbstractHelper;


class WebpackEncoreViewHelper extends AbstractHelper {

    /**
     * @var array
     */
    protected $entrypointsMap = [];

    /**
     * @var array
     */
    protected $templateEntrypointMap = [];

    /**
     * @var array
     */
    private $jsAssets=[];

    /**
     * @var array
     */
    private $cssAssets=[];

    /**
     * @var array
     */
    private $jsRootAssetFiles=[];

    /**
     * @var array
     */
    private $cssRootAssetFiles=[];

    /**
     * @var \Laminas\View\Model\ViewModel
     */
    private $layout;


    public function __invoke($webPackEntryPoint=null,$type=null)
    {

        if ( $webPackEntryPoint===null){
            return $this;
        }

        if (substr($webPackEntryPoint, 0, 4)==='auto' && $this->isRoot()===false){
            throw new Exception\InvalidArgumentException('helper with "auto*" parameter can be use only from root(layout)');
        }

        $layout=$this->getLayout();
        if ( $webPackEntryPoint ==='auto' || $webPackEntryPoint==='auto-route'){
            $templateFullName=$layout->getTemplate();
            $wpEntryPoint= $this->getWebpackentryFromTemplate($templateFullName);
            $this->loadAssets($wpEntryPoint, $type);
        }

        if (  $webPackEntryPoint ==='auto' || $webPackEntryPoint==='auto-child'){
            // find template name from child view ( assumed captureTo is "content")
            $childModel=$layout->getChildrenByCaptureTo('content',false);
            if (!is_array($childModel) ){
                throw new Exception\InvalidArgumentException('cannot detect child view model template name');
            }

            $templateFullName=$childModel[0]->getTemplate();
            $wpEntryPoint= $this->getWebpackentryFromTemplate($templateFullName);
            $this->loadAssets($wpEntryPoint, $type);

        }

        if (substr($webPackEntryPoint, 0, 4)!=='auto'){
            $this->loadAssets($webPackEntryPoint, $type);
        }

    }


    private function getWebpackentryFromTemplate($templateFullName)
    {

        $webpackEntrypoint='';
        $bOk=false;

        // First tentative: from template/entrypoint map
        if ( isset($this->templateEntrypointMap[$templateFullName]) ){
            $webpackEntrypoint=$this->templateEntrypointMap[$templateFullName];
            $bOk=true;
        }

        // Second tentative:if not found at step 1 then try to assume template name equal to entrypoint
        if ( $bOk===false){
            $matches=[];
            // resolve mezzio template (namspace::template)
            if (preg_match('#^(?P<namespace>[^:]+)::(?P<template>.*)$#', $templateFullName, $matches)) {
                //$namespace = $matches['namespace'];
                $webpackEntrypoint  = $matches['template'];
            }
        }

        return $webpackEntrypoint;

    }


    private function loadAssets($webPackEntryPoint,$type=null)
    {

        if ( $webPackEntryPoint==='*exclude'){
            return;
        }

        if ($type===null){
            $this->loadAssets($webPackEntryPoint, 'css');
            $this->loadAssets($webPackEntryPoint, 'js');
            return;
        }

        if (is_array($webPackEntryPoint) ){
            foreach ($webPackEntryPoint as $entryPoint){
                $this->loadAssets($entryPoint, $type);
            }
            return;
        }

        $assetFiles=[];

        if (! array_key_exists($webPackEntryPoint, $this->entrypointsMap)) {
            throw new Exception\InvalidArgumentException(
                'Entrypoint with name:(' . $webPackEntryPoint . ') is not defined.
                If you are using "auto*" parameter you need to map entry point to template in webpack_encore_view_helper_config -> template_entrypoint_map configuration
                or change entrypoint name in order to match template name with entrypoint name.
                If you are not using "auto*" parameter check entry point name"
            ');

        }else{
            $asset=$this->entrypointsMap[$webPackEntryPoint];
        }

        if (isset($asset[$type]) ){
            foreach ($asset[$type] as $element){
                array_push($assetFiles, $element);
            }
        }

        $isRoot=$this->isRoot();

        if ($type==='js' && $isRoot===false){
            $this->jsAssets=$assetFiles;
        }

        if ($type==='css' && $isRoot===false){
            $this->cssAssets=$assetFiles;
        }

        if ($type==='js' && $isRoot===true){
            $this->jsRootAssetFiles=array_merge($this->jsRootAssetFiles,$assetFiles);
        }

        if ($type==='css' && $isRoot===true){
            $this->cssRootAssetFiles=array_merge($this->cssRootAssetFiles,$assetFiles);
        }

    }


    public function render($type)
    {
         $output='';

        if ($type==='js'){
            $model='<script src="%s"></script>';
            $assetFilesChild=$this->jsAssets;
            $assetFilesRoot=$this->jsRootAssetFiles;
        }
        if ($type==='css'){
            $model='<link rel="stylesheet" href="%s">';
            $assetFilesChild=$this->cssAssets;
            $assetFilesRoot=$this->cssRootAssetFiles;
        }

        $assets=array_merge($assetFilesRoot,$assetFilesChild);
        $assets=array_unique($assets);

        foreach ($assets as $asset){
            $output=$output . "\r\n" .  sprintf($model,$asset);
        }

        return $output;

    }

    private function getLayout(): ?\Laminas\View\Model\ViewModel
    {
        if ($this->layout!==null){
            return $this->layout;
        }

        /* retrieve viewModelHelper in order to get current view model and its root. Root=layout */
        if (method_exists($this->getView(), 'plugin')) {
            /* @var $viewModelHelper \Laminas\View\Helper\ViewModel */
            $viewModelHelper = $this->view->plugin('view_model');
            $this->layout=$viewModelHelper->getRoot();
        }

        return $this->layout;

    }


    private function isRoot()
    {
        $isRoot=false;

        $viewModelHelper = $this->view->plugin('view_model');
        if ($this->getLayout()===$viewModelHelper->getCurrent() ){
            $isRoot=true;
        }

        return $isRoot;

    }

    /**
     * laminas asset view helper but return full link or script tags
     *
     * @param string $asset
     * @return string
     */
    public function asset($asset)
    {

        $model='';

        /* @var $asset \Laminas\View\Helper\Asset */
        $assetHelper = $this->getView()->plugin('asset');

        $assetResult=$assetHelper($asset);

        $ext = pathinfo($assetResult, PATHINFO_EXTENSION);

        if ($ext==='js'){
            $model='<script src="%s"></script>';
        }
        if ($ext==='css'){
            $model='<link rel="stylesheet" href="%s">';
        }

        return sprintf($model,$assetResult);
    }


    /**
     * @param array $templateEntryPointMap
     */
    public function setTemplateEntrypointMap($templateEntrypointMap)
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
