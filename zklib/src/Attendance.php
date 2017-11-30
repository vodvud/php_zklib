<?php

namespace ZK;

use ZKLib;

class Attendance
{
    /**
     * @param ZKLib $self
     * @return array [uid, id, state, timestamp]
     */
    public function get(ZKLib $self)
    {
        $command = Util::CMD_ATT_LOG_RRQ;
        $command_string = '';

        $session_id = $self->_command($command, $command_string, Util::COMMAND_TYPE_DATA);

        if ($session_id === false) {
            return [];
        }

        if ($bytes = Util::getSize($self)) {
            while ($bytes > 0) {
                @socket_recvfrom($self->_zkclient, $data_recv, 1032, 0, $self->_ip, $self->_port);
                array_push($self->_attendance_data, $data_recv);
                $bytes -= 1024;
            }

            $self->_session_id = $session_id;
            @socket_recvfrom($self->_zkclient, $data_recv, 1024, 0, $self->_ip, $self->_port);
        }

        $attendance = [];
        if (count($self->_attendance_data) > 0) {
            # The first 4 bytes don't seem to be related to the user
            for ($x = 0; $x < count($self->_attendance_data); $x++) {
                if ($x > 0) {
                    $self->_attendance_data[$x] = substr($self->_attendance_data[$x], 8);
                }
            }

            $attData = implode('', $self->_attendance_data);
            $attData = substr($attData, 10);

            while (strlen($attData) > 40) {
                $u = unpack('H78', substr($attData, 0, 39));

                $u1 = hexdec(substr($u[1], 4, 2));
                $u2 = hexdec(substr($u[1], 6, 2));
                $uid = $u1 + ($u2 * 256);
                $id = preg_replace('/[^\w]/', '', hex2bin(substr($u[1], 6, 20)));
                $state = hexdec(substr($u[1], 56, 2));
                $timestamp = Util::decodeTime(hexdec(Util::reverseHex(substr($u[1], 58, 8))));

                $attendance[] = [
                    'uid' => $uid,
                    'id' => $id,
                    'state' => $state,
                    'timestamp' => $timestamp
                ];

                $attData = substr($attData, 40);
            }

        }

        return $attendance;
    }

    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function clear(ZKLib $self)
    {
        $command = Util::CMD_CLEAR_ATT_LOG;
        $command_string = '';

        return $self->_command($command, $command_string);
    }
}
