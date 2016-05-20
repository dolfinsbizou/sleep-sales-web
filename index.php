<?php

require 'src/Astaroth.php';

function debug($vars){
    echo '<pre>';
    print_r($vars);
    echo '</pre>';
    return $vars;
}

Astaroth::run();