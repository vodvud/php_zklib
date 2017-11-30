<?php

namespace ZK;

use ZKLib;

class User
{
    /**
     * @param ZKLib $self
     * @param int $uid
     * @param string $userid (max length = 9)
     * @param string $name (max length = 24)
     * @param string $password (max length = 8)
     * @param int $role Default Util::LEVEL_USER
     * @return bool|mixed
     */
    public function set(ZKLib $self, $uid, $userid, $name, $password, $role = Util::LEVEL_USER)
    {
        if (empty($uid) || strlen($userid) > 9 || strlen($name) > 24 || strlen($password) > 8) {
            return false;
        }

        $command = Util::CMD_SET_USER;
        $byte1 = chr((int)($uid % 256));
        $byte2 = chr((int)($uid >> 8));
        $command_string = implode('', [
            $byte1,
            $byte2,
            chr($role),
            str_pad($password, 8, chr(0)),
            str_pad($name, 28, chr(0)),
            str_pad(chr(1), 9, chr(0)),
            str_pad($userid, 9, chr(0)),
            str_repeat(chr(0), 15)
        ]);

        return $self->_command($command, $command_string);
    }

    /**
     * @param ZKLib $self
     * @return array [userid, name, cardno, uid, role, password]
     */
    public function get(ZKLib $self)
    {
        $command = Util::CMD_USER_TEMP_RRQ;
        $command_string = chr(Util::FCT_USER);

        $session_id = $self->_command($command, $command_string, Util::COMMAND_TYPE_DATA);

        if ($session_id === false) {
            return [];
        }

        if ($bytes = Util::getSize($self)) {
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

            $userData = implode('', $self->_user_data);

            $userData = substr($userData, 11);

            while (strlen($userData) > 72) {
                $u = unpack('H144', substr($userData, 0, 72));

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

                if ($name == '') {
                    $name = $userid;
                }

                $users[$userid] = [
                    'userid' => $userid,
                    'name' => $name,
                    'cardno' => $cardno,
                    'uid' => $uid,
                    'role' => intval($role),
                    'password' => $password
                ];

                $userData = substr($userData, 72);
            }
        }

        return $users;
    }

    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function clear(ZKLib $self)
    {
        $command = Util::CMD_CLEAR_DATA;
        $command_string = '';

        return $self->_command($command, $command_string);
    }

    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function clearAdmin(ZKLib $self)
    {
        $command = Util::CMD_CLEAR_ADMIN;
        $command_string = '';

        return $self->_command($command, $command_string);
    }

    /**
     * @param ZKLib $self
     * @param integer $uid
     * @return bool|mixed
     */
    public function remove(ZKLib $self, $uid)
    {
        $command = Util::CMD_DELETE_USER;
        $byte1 = chr((int)($uid % 256));
        $byte2 = chr((int)($uid >> 8));
        $command_string = ($byte1 . $byte2);

        return $self->_command($command, $command_string);
    }
}