<?php

namespace ZK;

use ZKLib;

class Time
{
    /**
     * @param ZKLib $self
     * @param string $t Format: "Y-m-d H:i:s"
     * @return bool|mixed
     */
    public function set(ZKLib $self, $t)
    {
        $command = Constant::CMD_SET_TIME;
        $command_string = pack('I', Constant::encode_time($t));

        return $self->_command($command, $command_string);
    }

    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function get(ZKLib $self)
    {
        $command = Constant::CMD_GET_TIME;
        $command_string = '';

        $ret = $self->_command($command, $command_string);

        if ($ret) {
            return Constant::decode_time(hexdec(Constant::reverseHex(bin2hex($ret))));
        } else {
            return false;
        }
    }
}