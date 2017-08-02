<?php
require(__DIR__ . '/vendor/autoload.php');

class ZKLib
{
    public $_ip;
    public $_port;
    public $_zkclient;

    public $_data_recv = '';
    public $_session_id = 0;
    public $_user_data = [];
    public $_attendance_data = [];

    /**
     * ZKLib constructor.
     * @param string $ip Device IP
     * @param integer $port Default: 4370
     */
    public function __construct($ip, $port = 4370)
    {
        $this->_ip = $ip;
        $this->_port = $port;

        $this->_zkclient = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        $timeout = ['sec' => 60, 'usec' => 500000];
        socket_set_option($this->_zkclient, SOL_SOCKET, SO_RCVTIMEO, $timeout);

    }

    /**
     * This function calculates the chksum of the packet to be sent to the
     * time clock
     * Copied from zkemsdk.c
     *
     * @inheritdoc
     */
    public function _createChkSum($p)
    {
        $l = count($p);
        $chksum = 0;
        $i = $l;
        $j = 1;
        while ($i > 1) {
            $u = unpack('S', pack('C2', $p['c' . $j], $p['c' . ($j + 1)]));

            $chksum += $u[1];

            if ($chksum > ZKConst::USHRT_MAX) {
                $chksum -= ZKConst::USHRT_MAX;
            }
            $i -= 2;
            $j += 2;
        }

        if ($i) {
            $chksum = $chksum + $p['c' . strval(count($p))];
        }

        while ($chksum > ZKConst::USHRT_MAX) {
            $chksum -= ZKConst::USHRT_MAX;
        }

        if ($chksum > 0) {
            $chksum = -($chksum);
        } else {
            $chksum = abs($chksum);
        }

        $chksum -= 1;
        while ($chksum < 0) {
            $chksum += ZKConst::USHRT_MAX;
        }

        return pack('S', $chksum);
    }

    /**
     * This function puts a the parts that make up a packet together and
     * packs them into a byte string
     *
     * @inheritdoc
     */
    public function _createHeader($command, $chksum, $session_id, $reply_id, $command_string)
    {
        $buf = pack('SSSS', $command, $chksum, $session_id, $reply_id) . $command_string;

        $buf = unpack('C' . (8 + strlen($command_string)) . 'c', $buf);

        $u = unpack('S', $this->_createChkSum($buf));

        if (is_array($u)) {
            while (list($key) = each($u)) {
                $u = $u[$key];
                break;
            }
        }
        $chksum = $u;

        $reply_id += 1;

        if ($reply_id >= ZKConst::USHRT_MAX) {
            $reply_id -= ZKConst::USHRT_MAX;
        }

        $buf = pack('SSSS', $command, $chksum, $session_id, $reply_id);

        return $buf . $command_string;

    }

    /**
     * Checks a returned packet to see if it returned ZKConst::CMD_ACK_OK,
     * indicating success
     *
     * @inheritdoc
     */
    public function _checkValid($reply)
    {
        $u = unpack('H2h1/H2h2', substr($reply, 0, 8));

        $command = hexdec($u['h2'] . $u['h1']);
        if ($command == ZKConst::CMD_ACK_OK) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create and send command to device
     *
     * @param string $command
     * @param string $command_string
     * @param string $type
     * @return bool|mixed
     */
    public function _command($command, $command_string, $type = ZKConst::COMMAND_TYPE_GENERAL)
    {
        $chksum = 0;
        $session_id = $this->_session_id;

        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr($this->_data_recv, 0, 8));
        $reply_id = hexdec($u['h8'] . $u['h7']);

        $buf = $this->_createHeader($command, $chksum, $session_id, $reply_id, $command_string);

        socket_sendto($this->_zkclient, $buf, strlen($buf), 0, $this->_ip, $this->_port);

        try {
            @socket_recvfrom($this->_zkclient, $this->_data_recv, 1024, 0, $this->_ip, $this->_port);

            $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6', substr($this->_data_recv, 0, 8));

            switch ($type) {
                case ZKConst::COMMAND_TYPE_GENERAL:
                    $this->_session_id = hexdec($u['h6'] . $u['h5']);
                    $ret = substr($this->_data_recv, 8);
                    break;
                case ZKConst::COMMAND_TYPE_DATA:
                    $ret = hexdec($u['h6'] . $u['h5']);
                    break;
                default:
                    $ret = false;
            }

            return $ret;
        } catch (ErrorException $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Connect to device
     *
     * @return bool
     */
    public function connect()
    {
        return (new ZKConnect())->connect($this);
    }

    /**
     * Disconnect from device
     *
     * @return bool
     */
    public function disconnect()
    {
        return (new ZKConnect())->disconnect($this);
    }

    /**
     * Get device version
     *
     * @return bool|mixed
     */
    public function version()
    {
        return (new ZKVersion())->get($this);
    }

    /**
     * Get OS version
     *
     * @return bool|mixed
     */
    public function osVersion()
    {
        return (new ZKOs())->get($this);
    }

    /**
     * Get platform
     *
     * @return bool|mixed
     */
    public function platform()
    {
        return (new ZKPlatform())->get($this);
    }

    /**
     * Get firmware version
     *
     * @return bool|mixed
     */
    public function fmVersion()
    {
        return (new ZKPlatform())->getVersion($this);
    }

    /**
     * Get work code
     *
     * @return bool|mixed
     */
    public function workCode()
    {
        return (new ZKWorkCode())->get($this);
    }

    /**
     * Get SSR
     *
     * @return bool|mixed
     */
    public function ssr()
    {
        return (new ZKSsr())->get($this);
    }

    /**
     * Get pin width
     *
     * @return bool|mixed
     */
    public function pinWidth()
    {
        return (new ZKPin())->width($this);
    }

    /**
     * @return bool|mixed
     */
    public function faceFunctionOn()
    {
        return (new ZKFace())->on($this);
    }

    /**
     * Get device serial number
     *
     * @return bool|mixed
     */
    public function serialNumber()
    {
        return (new ZKSerialNumber())->get($this);
    }

    /**
     * Get device name
     *
     * @return bool|mixed
     */
    public function deviceName()
    {
        return (new ZKDevice())->name($this);
    }

    /**
     * Disable device
     *
     * @return bool|mixed
     */
    public function disableDevice()
    {
        return (new ZKDevice())->disable($this);
    }

    /**
     * Enable device
     *
     * @return bool|mixed
     */
    public function enableDevice()
    {
        return (new ZKDevice())->enable($this);
    }

    /**
     * Get users data
     *
     * @return array|bool
     */
    public function getUser()
    {
        return (new ZKUser())->get($this);
    }

    /**
     * Set user data
     *
     * @param int $uid Unique ID
     * @param string $userid ID in DB (same like $uid)
     * @param string $name
     * @param string $password
     * @param int $role Default ZKConst::LEVEL_USER
     * @return bool|mixed
     */
    public function setUser($uid, $userid, $name, $password, $role = ZKConst::LEVEL_USER)
    {
        return (new ZKUser())->set($this, $uid, $userid, $name, $password, $role);
    }

    /**
     * Remove users
     *
     * @return bool|mixed
     */
    public function clearUser()
    {
        return (new ZKUser())->clear($this);
    }

    /**
     * Remove admin
     *
     * @return bool|mixed
     */
    public function clearAdmin()
    {
        return (new ZKUser())->clearAdmin($this);
    }

    /**
     * Get attendance log
     *
     * @return array
     */
    public function getAttendance()
    {
        return (new ZKAttendance())->get($this);
    }

    /**
     * Clear attendance log
     *
     * @return bool|mixed
     */
    public function clearAttendance()
    {
        return (new ZKAttendance())->clear($this);
    }

    /**
     * Set device time
     *
     * @param string $t Format: "Y-m-d H:i:s"
     * @return bool|mixed
     */
    public function setTime($t)
    {
        return (new ZKTime())->set($this, $t);
    }

    /**
     * Get device time
     *
     * @return bool|mixed Format: "Y-m-d H:i:s"
     */
    public function getTime()
    {
        return (new ZKTime())->get($this);
    }
}