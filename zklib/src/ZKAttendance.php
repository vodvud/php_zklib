<?php

class ZKAttendance
{
    /**
     * @param ZKLib $self
     * @return array
     */
    public function get(ZKLib $self)
    {
        $command = ZKConst::CMD_ATT_LOG_RRQ;
        $command_string = '';

        $session_id = $self->_command($command, $command_string, ZKConst::COMMAND_TYPE_DATA);

        if ($session_id === false) {
            return [];
        }

        if ($bytes = ZKConst::getSize($self)) {
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

            $attendanceData = implode('', $self->_attendance_data);
            $attendanceData = substr($attendanceData, 10);

            while (strlen($attendanceData) > 40) {
                $u = unpack('H78', substr($attendanceData, 0, 39));

                $u1 = hexdec(substr($u[1], 4, 2));
                $u2 = hexdec(substr($u[1], 6, 2));
                $uid = $u1 + ($u2 * 256);
                $id = intval(str_replace("\0", '', hex2bin(substr($u[1], 6, 8))));
                $state = hexdec(substr($u[1], 56, 2));
                $timestamp = ZKConst::decode_time(hexdec(ZKConst::reverseHex(substr($u[1], 58, 8))));

                array_push($attendance, [
                    'uid' => $uid,
                    'id' => $id,
                    'state' => $state,
                    'timestamp' => $timestamp
                ]);

                $attendanceData = substr($attendanceData, 40);
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
        $command = ZKConst::CMD_CLEAR_ATT_LOG;
        $command_string = '';

        return $self->_command($command, $command_string);
    }
}
