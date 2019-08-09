<?php
include('zklib/ZKLib.php');
date_default_timezone_set('Asia/Manila'); //Default Timezone
$users = [];
//Main device
$zk = new ZKLib('192.168.5.166');
$ret = $zk->connect();
if ($ret) {
    $zk->disableDevice();
    //Get users
    $users = $zk->getUser();
    $zk->enableDevice();
}
$zk->disconnect();


if (count($users) > 0) {
    //Sync devices list

    $devices = [
        '192.168.5.167',
        '192.168.5.163'
    ];

    foreach ($devices as $ip) {
        $zk = new ZKLib($ip);
        $ret = $zk->connect();
        if ($ret) {
            $zk->disableDevice();

            //Remove old users
            $zk->clearUsers();

            foreach ($users as $user) {
                //Add user
                echo $zk->setUser(
                    $user['uid'],
                    $user['userid'],
                    $user['name'],
                    $user['password'],
                    $user['role']
                );
            }
            $zk->enableDevice();
        }
        $zk->disconnect();
    }

    $zk->setTime(date('Y-m-d H:i:s'));
}
