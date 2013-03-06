<?php
spl_autoload_register(function ($classname) {

    if (preg_match('/[a-zA-Z]+Controller$/', $classname)) {

        require $_SERVER['DOCUMENT_ROOT'] . '/lib/controllers/' . $classname . '.php';
        return true;

    } elseif (preg_match('/[a-zA-Z]+Model$/', $classname)) {

        require $_SERVER['DOCUMENT_ROOT'] . '/lib/models/' . $classname . '.php';
        return true;

	} elseif (preg_match('/[a-zA-Z]+Helper$/', $classname)) {

        require $_SERVER['DOCUMENT_ROOT'] . '/lib/helpers/' . $classname . '.php';
        return true;

    }
});
?>