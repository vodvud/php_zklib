<?php

namespace ZK;

use ZKLib;
use ErrorException;
use Exception;

class User
{
    /**
     * @param ZKLib $self
     * @param int $uid
     * @param string $userid
     * @param string $name
     * @param string $password
     * @param int $role Default Constant::LEVEL_USER
     * @return bool|mixed
     */
    public function set(ZKLib $self, $uid, $userid, $name, $password, $role = Constant::LEVEL_USER)
    {
        $command = Constant::CMD_SET_USER;
        $byte1 = chr((int)($uid % 256));
        $byte2 = chr((int)($uid >> 8));
        $command_string = $byte1 . $byte2 . chr($role) . str_pad($password, 8, chr(0)) . str_pad($name, 28, chr(0))
            . str_pad(chr(1), 9, chr(0)) . str_pad($userid, 8, chr(0)) . str_repeat(chr(0), 16);

        return $self->_command($command, $command_string);
    }

    /**
     * @param ZKLib $self
     * @return array|bool
     */
    public function get(ZKLib $self)
    {
        $command = Constant::CMD_USER_TEMP_RRQ;
        $command_string = chr(5);

        $session_id = $self->_command($command, $command_string, Constant::COMMAND_TYPE_DATA);

        if ($session_id === false) {
            return [];
        }

        try {
            if ($bytes = Constant::getSize($self)) {
                while ($bytes > 0) {
                    @socket_recvfrom($self->_zkclient, $data_recv, 1032, 0, $self->_ip, $self->_port);
                    array_push($self->_user_data, $data_recv);
                    $bytes -= 1024;
                }

                $self->_session_id = $session_id;
                @socket_recvfrom($self->_zkclient, $data_recv, 1024, 0, $self->_ip, $self->_port);
            }


            $users = [];
            if (count($self->_user_data) > 0) {
                //The first 4 bytes don't seem to be related to the user
                for ($x = 0; $x < count($self->_user_data); $x++) {
                    if ($x > 0) {
                        $self->_user_data[$x] = substr($self->_user_data[$x], 8);
                    }
                }

                $userdata = implode('', $self->_user_data);

                $userdata = substr($userdata, 11);

                while (strlen($userdata) > 72) {
                    $u = unpack('H144', substr($userdata, 0, 72));

                    $u1 = hexdec(substr($u[1], 2, 2));
                    $u2 = hexdec(substr($u[1], 4, 2));
                    $uid = $u1 + ($u2 * 256);
                    $cardno = hexdec(substr($u[1], 78, 2) . substr($u[1], 76, 2) . substr($u[1], 74, 2) . substr($u[1], 72, 2)) . ' ';
                    $role = hexdec(substr($u[1], 4, 4)) . ' ';
                    $password = hex2bin(substr($u[1], 8, 16)) . ' ';
                    $name = hex2bin(substr($u[1], 24, 74)) . ' ';
                    $userid = hex2bin(substr($u[1], 98, 72)) . ' ';

                    //Clean up some messy characters from the user name
                    $password = explode(chr(0), $password, 2);
                    $password = $password[0];
                    $userid = explode(chr(0), $userid, 2);
                    $userid = $userid[0];
                    $name = explode(chr(0), $name, 3);
                    $name = utf8_encode($name[0]);
                    $cardno = str_pad($cardno, 11, '0', STR_PAD_LEFT);

                    if ($name == "") {
                        $name = $uid;
                    }

                    $users[$uid] = [
                        'userid' => $userid,
                        'name' => $name,
                        'cardno' => $cardno,
                        'uid' => $uid,
                        'role' => intval($role),
                        'password' => $password
                    ];

                    $userdata = substr($userdata, 72);
                }
            }

            return $users;
        } catch (ErrorException $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function clear(ZKLib $self)
    {
        $command = Constant::CMD_CLEAR_DATA;
        $command_string = '';

        return $self->_command($command, $command_string);
    }

    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function clearAdmin(ZKLib $self)
    {
        $command = Constant::CMD_CLEAR_ADMIN;
        $command_string = '';

        return $self->_command($command, $command_string);
    }
}