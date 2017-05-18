<?php

interface IFestiEngine
{
    public function fetch($template, $vars = array());
    public function getUrl();
}