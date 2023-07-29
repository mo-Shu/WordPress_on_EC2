<?php
$wpdb->add_database(array(
        'host'     => DB_HOST,
        'user'     => DB_USER,
        'password' => DB_PASSWORD,
        'name'     => DB_NAME,
        'write'    => 1,
        'read'     => 2,
));

// $wpdb->add_database(array(
//         'host'     => ReadEndpoint_Address,
//         'user'     => DB_USER,
//         'password' => DB_PASSWORD,
//         'name'     => DB_NAME,
//         'write'    => 0,
//         'read'     => 1,
// ));
