<html>
<head>
    <title>ZK Test</title>
</head>

<body>
<?php
    $enableGetDeviceInfo = true;
    $enableGetUsers = true;
    $enableGetData = true;

    include('zklib/ZKLib.php');

    $zk = new ZKLib(
        '192.168.1.100' //your device IP
    );

    $ret = $zk->connect();
    if ($ret) {
        $zk->disableDevice();
        $zk->setTime(date('Y-m-d H:i:s')); // Synchronize time
        ?>
        <?php if($enableGetDeviceInfo === true) { ?>
        <table border="1" cellpadding="5" cellspacing="2">
            <tr>
                <td><b>Status</b></td>
                <td>Connected</td>
                <td><b>Version</b></td>
                <td><?php echo($zk->version()); ?></td>
                <td><b>OS Version</b></td>
                <td><?php echo($zk->osVersion()); ?></td>
                <td><b>Platform</b></td>
                <td><?php echo($zk->platform()); ?></td>
            </tr>
            <tr>
                <td><b>Firmware Version</b></td>
                <td><?php echo($zk->fmVersion()); ?></td>
                <td><b>WorkCode</b></td>
                <td><?php echo($zk->workCode()); ?></td>
                <td><b>SSR</b></td>
                <td><?php echo($zk->ssr()); ?></td>
                <td><b>Pin Width</b></td>
                <td><?php echo($zk->pinWidth()); ?></td>
            </tr>
            <tr>
                <td><b>Face Function On</b></td>
                <td><?php echo($zk->faceFunctionOn()); ?></td>
                <td><b>Serial Number</b></td>
                <td><?php echo($zk->serialNumber()); ?></td>
                <td><b>Device Name</b></td>
                <td><?php echo($zk->deviceName()); ?></td>
                <td><b>Get Time</b></td>
                <td><?php echo($zk->getTime()); ?></td>
            </tr>
        </table>
        <?php } ?>
        <hr/>
        <?php if($enableGetUsers === true) { ?>
        <table border="1" cellpadding="5" cellspacing="2" style="float: left; margin-right: 10px;">
            <tr>
                <th colspan="6">Data User</th>
            </tr>
            <tr>
                <th>UID</th>
                <th>ID</th>
                <th>Name</th>
                <th>Card #</th>
                <th>Role</th>
                <th>Password</th>
            </tr>
            <?php
            try {
                //$zk->setUser(1, '1', 'User1', '', ZK\Util::LEVEL_USER);
                //$zk->setUser(2, '2', 'User2', '', ZK\Util::LEVEL_USER);
                //$zk->setUser(3, '3', 'User3', '', ZK\Util::LEVEL_USER);
                //$zk->setUser(5, '5', 'Admin', '1234', ZK\Util::LEVEL_ADMIN);
                $users = $zk->getUser();
                sleep(1);
                foreach ($users as $uItem) {
                    ?>
                    <tr>
                        <td><?php echo($uItem['uid']); ?></td>
                        <td><?php echo($uItem['userid']); ?></td>
                        <td><?php echo($uItem['name']); ?></td>
                        <td><?php echo($uItem['cardno']); ?></td>
                        <td><?php echo(ZK\Util::getUserRole($uItem['role'])); ?></td>
                        <td><?php echo($uItem['password']); ?>&nbsp;</td>
                    </tr>
                    <?php
                }
            } catch (Exception $e) {
                header("HTTP/1.0 404 Not Found");
                header('HTTP', true, 500); // 500 internal server error
            }
            //$zk->clearAdmin();
            //$zk->clearUsers();
            //$zk->removeUser(1);
            ?>
        </table>
        <?php } ?>
        <?php if ($enableGetData === true) { ?>
            <table border="1" cellpadding="5" cellspacing="2">
                <tr>
                    <th colspan="7">Data Attendance</th>
                </tr>
                <tr>
                    <th>UID</th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>State</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Type</th>
                </tr>
                <?php
                    $attendance = $zk->getAttendance();
                    if (count($attendance) > 0) {
                        $attendance = array_reverse($attendance, true);
                        sleep(1);
                        foreach ($attendance as $attItem) {
                            ?>
                            <tr>
                                <td><?php echo($attItem['uid']); ?></td>
                                <td><?php echo($attItem['id']); ?></td>
                                <td><?php echo(isset($users[$attItem['id']]) ? $users[$attItem['id']]['name'] : $attItem['id']); ?></td>
                                <td><?php echo(ZK\Util::getAttState($attItem['state'])); ?></td>
                                <td><?php echo(date("d-m-Y", strtotime($attItem['timestamp']))); ?></td>
                                <td><?php echo(date("H:i:s", strtotime($attItem['timestamp']))); ?></td>
                                <td><?php echo(ZK\Util::getAttType($attItem['type'])); ?></td>
                            </tr>
                            <?php
                        }
                    }
                ?>
            </table>
            <?php
                if (count($attendance) > 0) {
                    //$zk->clearAttendance(); // Remove attendance log only if not empty
                }
            ?>
        <?php } ?>
        <?php
        $zk->enableDevice();
        $zk->disconnect();
    }
?>
</body>
</html>
