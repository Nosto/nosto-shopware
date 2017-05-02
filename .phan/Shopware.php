<?php

class Shopware extends Enlight_Application
{
    /**
     * @return \Shopware\Components\Logger
     */
    public function PluginLogger() {}

    /**
     * @return Shopware_Components_Auth
     */
    public function Auth() {}

    /**
     * @return string
     */
    public function SessionID() {}
}