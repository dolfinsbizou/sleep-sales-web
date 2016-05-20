<?php

Astaroth::set(array(

    'plugins' => array(

        'Session',
        'Errors' => array(
            'catch_errors' => true,
            '404_redirect' => 'pages/error404'
        ),
        'Flash',
        'Db' => array(
            'dsn' => 'mysql:dbname=bdemontreal;host=127.0.0.1',
            'username' => 'root',
            'passwd' => '123',
            'debug' => false
        ),
        'UserManager' => [
            'class_user' => \App\Db\UserDb::class,
            'attr_username' => 'username',
            'attr_passwd' => 'password'
        ],
        'Controller',
    ),

    'app.layout' => '_layout',
    'astaroth.url_rewriting' => true,
    // WARNING: change this to false when in production
    'astaroth.debug' => true,


    
));

Astaroth::set('app.routes', [
    'index' => [
        'action' => 'Page/index'
    ]
]);